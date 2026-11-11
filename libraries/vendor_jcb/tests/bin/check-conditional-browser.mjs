/**
 * Execute freshly compiled browser conditions against the generated PHP rule.
 *
 * Run separately from PHPUnit: node bin/check-conditional-browser.mjs
 * Set JCB_TEST_PHP when the PHP executable is not on PATH or in .runtime/bin.
 * The DOM boundary is deliberately small; installed Joomla GUI tests remain
 * responsible for real widget rendering, field submission and AJAX integration.
 */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const testsRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const localPhp = resolve(testsRoot, '.runtime/bin/php');
const php = process.env.JCB_TEST_PHP || (existsSync(localPhp) ? localPhp : 'php');
const cases = JSON.parse(execFileSync(php, [
  '-d', 'memory_limit=1G', resolve(testsRoot, 'bin/export-conditional-browser.php'),
], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 }));

/** Build a controlled jQuery/DOM boundary around the emitted source. */
function browser(fixture) {
  const fields = new Map();
  const containers = new Map();
  const listeners = new Map();
  const ready = [];
  let context;

  for (const condition of fixture.conditions) {
    for (const target of condition.target_field) {
      fields.set(target.name, {
        name: target.name, required: target.required === 'yes',
        attributes: {}, classes: new Set(),
      });
      const key = fixture.containerBindings?.[target.name] ?? target.name;
      if (!containers.has(key)) {
        containers.set(key, { style: { display: fixture.initialDisplay?.[target.name] ?? '' } });
      }
    }
  }

  function selection(selector) {
    const nodes = typeof selector === 'object' ? [selector] : [];
    const name = typeof selector === 'string'
      ? selector.replace(/^#jform_|^\./, '').split(' ')[0].replace(/-lbl$/, '').replace(/_id$/, '')
      : selector.name;
    const absent = fixture.absent?.includes(name);
    const field = absent ? undefined : fields.get(name);
    const value = () => context.fixtureValues?.[name];
    const collection = {
      val() { return value(); },
      prop(key, next) {
        if (arguments.length === 2) {
          if (field) field[key] = next;
          return collection;
        }
        if (nodes.length) return nodes[0][key];
        return key === 'checked' ? Boolean(value()) : field?.[key];
      },
      attr(key, next) {
        if (field) field.attributes[key] = next;
        return collection;
      },
      removeAttr(key) {
        if (field) {
          if (key === 'required') field.required = false;
          delete field.attributes[key];
        }
        return collection;
      },
      addClass(key) { field?.classes.add(key); return collection; },
      removeClass(key) { field?.classes.delete(key); return collection; },
      is() { return nodes[0]?.checked ?? false; },
      each(callback) {
        for (const selected of value() ?? []) callback.call({ value: selected, checked: true });
        return collection;
      },
      closest() {
        const key = fixture.containerBindings?.[name] ?? name;
        const container = absent ? undefined : containers.get(key);
        return {
          get() { return container ? [container] : []; },
          show() { if (container) container.style.display = ''; },
          hide() { if (container) container.style.display = 'none'; },
        };
      },
      on(event, delegated, callback) {
        const watched = typeof delegated === 'string' ? delegated : selector;
        listeners.set(`${event}:${watched}`, callback ?? delegated);
        return collection;
      },
    };
    return collection;
  }

  context = vm.createContext({
    jQuery: selection,
    document: { addEventListener(event, callback) { if (event === 'DOMContentLoaded') ready.push(callback); } },
  });
  vm.runInContext(fixture.script, context, { filename: `${fixture.name}.js` });
  vm.runInContext(fixture.footer.replace(/<\/?script[^>]*>/g, ''), context);

  return {
    fields,
    context,
    step(step, initial) {
      // Construct arrays inside the JavaScript realm used by generated .constructor checks.
      vm.runInContext(`fixtureValues = JSON.parse(${JSON.stringify(JSON.stringify(step.values))});`, context);
      if (initial) {
        for (const callback of ready) callback();
      } else if (step.event === 'refresh') {
        context.jcbUpdateParityConditions();
      } else {
        const name = step.event ?? fixture.conditions[0].match_name;
        const callback = listeners.get(`change:#jform_${name}`)
          ?? listeners.get(`change:#jform_${name}_id`);
        assert.equal(typeof callback, 'function', `missing generated change handler for ${name}`);
        callback();
      }
    },
    visible(name) {
      const key = fixture.containerBindings?.[name] ?? name;
      return containers.get(key)?.style.display !== 'none';
    },
  };
}

let transitions = 0;
for (const fixture of cases) {
  if (fixture.error) {
    assert.ok(fixture.compileError?.includes(fixture.error), fixture.name);
    continue;
  }
  if (fixture.conditions.length === 0) {
    assert.equal(fixture.script, '', fixture.name);
    assert.equal(fixture.footer, '', fixture.name);
    assert.deepEqual(fixture.groups, [], fixture.name);
    continue;
  }

  const page = browser(fixture);
  assert.equal(typeof page.context.jcbUpdateParityConditions, 'function', fixture.name);
  assert.ok(!fixture.script.includes('not_required'), fixture.name);
  assert.ok(!fixture.script.includes('updateFieldRequired'), fixture.name);
  for (const [index, step] of fixture.steps.entries()) {
    const label = `${fixture.name}, transition ${index + 1}`;
    page.step(step, index === 0);
    assert.equal(step.phpRequired.details ?? false, step.required, `${label}: PHP required`);
    assert.equal(page.fields.get('details').required, step.required, `${label}: browser required`);
    assert.equal(page.visible('details'), step.visible ?? step.required, `${label}: browser visibility`);
    assert.equal(page.context.jcbUpdateParityConditionsBaseline.length, fixture.baselineCount ?? 1, `${label}: baseline`);
    transitions++;
  }
}

console.log(`Conditional browser/PHP parity passed: ${cases.length} fixtures, ${transitions} transitions.`);
