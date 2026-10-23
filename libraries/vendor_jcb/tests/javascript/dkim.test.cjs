const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const {runInNewContext} = require('node:vm');

const source = readFileSync(resolve(__dirname, '../../../../admin/compiler/joomla_6/dkim.js'), 'utf8');

function fixture(readyState, initial = {}) {
  const elements = {};
  for (const name of ['domain', 'selector', 'key', 'public_key', 'value']) {
    elements['#jform_dkim_' + name] = {
      value: initial[name] || '',
      listeners: {},
      selected: false,
      addEventListener(name, listener) { this.listeners[name] = listener; },
      select() { this.selected = true; },
    };
  }
  elements['#a_dkim_domain'] = {textContent: ''};
  const callbacks = {};
  const context = {
    window: {location: {hostname: 'mail.example.test'}},
    document: {
      readyState,
      querySelector(selector) { return elements[selector] || null; },
      addEventListener(name, listener, options) { callbacks[name] = {listener, options}; },
    },
  };
  runInNewContext(source, context);
  return {context, elements, callbacks};
}

test('deferred DKIM asset waits for controls and initializes DNS values', () => {
  const {elements, callbacks, context} = fixture('loading', {selector: 'vdm'});
  assert.equal(typeof context.vdm_dkim, 'function', 'existing inline callbacks require a global function');
  assert.equal(elements['#jform_dkim_domain'].value, '');
  assert.equal(callbacks.DOMContentLoaded.options.once, true);
  callbacks.DOMContentLoaded.listener();
  assert.equal(elements['#jform_dkim_domain'].value, 'mail.example.test');
  assert.equal(elements['#a_dkim_domain'].textContent, 'mail.example.test');
  assert.equal(elements['#jform_dkim_key'].value, 'vdm._domainkey');
  assert.equal(elements['#jform_dkim_value'].value, 'v=DKIM1;k=rsa;g=*;s=email;h=sha1;t=s;p=PUBLICKEY');
});

test('existing domain and configured public key survive DOM-ready initialization', () => {
  const {elements, callbacks} = fixture('complete', {domain: 'configured.example', selector: 'mail', public_key: 'ABC123'});
  assert.equal(Object.keys(callbacks).length, 0);
  assert.equal(elements['#jform_dkim_domain'].value, 'configured.example');
  assert.equal(elements['#jform_dkim_key'].value, 'mail._domainkey');
  assert.equal(elements['#jform_dkim_value'].value, 'v=DKIM1;k=rsa;g=*;s=email;h=sha1;t=s;p=ABC123');
});

test('global callback updates changed DNS fields and restores empty-key placeholder', () => {
  const {elements, context} = fixture('complete', {domain: 'original.example', public_key: 'OLD'});
  elements['#jform_dkim_domain'].value = 'updated.example';
  elements['#jform_dkim_selector'].value = 'new-selector';
  elements['#jform_dkim_public_key'].value = '';
  context.vdm_dkim();
  assert.equal(elements['#a_dkim_domain'].textContent, 'updated.example');
  assert.equal(elements['#jform_dkim_key'].value, 'new-selector._domainkey');
  assert.equal(elements['#jform_dkim_value'].value, 'v=DKIM1;k=rsa;g=*;s=email;h=sha1;t=s;p=PUBLICKEY');
});

test('clicking either DNS output selects its value for copying', () => {
  const {elements} = fixture('complete');
  for (const name of ['key', 'value']) {
    const input = elements['#jform_dkim_' + name];
    input.listeners.click.call(input);
    assert.equal(input.selected, true);
  }
});

test('a removed or replaced field does not break other configuration controls', () => {
  const {elements, context} = fixture('loading');
  delete elements['#jform_dkim_public_key'];
  assert.doesNotThrow(() => context.vdm_dkim());
});
