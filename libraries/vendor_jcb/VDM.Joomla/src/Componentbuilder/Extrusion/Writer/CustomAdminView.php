<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    24th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Extrusion\Writer;


use VDM\Joomla\Componentbuilder\Extrusion\Abstraction\Writer;
use VDM\Joomla\Componentbuilder\Extrusion\Config;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Report;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Resolved;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\Source;
use VDM\Joomla\Componentbuilder\Extrusion\Registry\View;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Guid;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Pairing;
use VDM\Joomla\Componentbuilder\Extrusion\Resolver\Text;
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the administrator views a component builds outside its tables.
 *
 * An import screen, a dashboard, a wizard: administrator views with no table
 * behind them are JCB's custom admin views, and their whole body is the
 * template the reader recovered. Every recovered administrator template is a
 * candidate; the ones a resolved table view answers for are that view's own
 * generated output and are passed over, and what remains is written whole --
 * body, php, name and its dynamic get -- so the component's administrator
 * arrives with every screen it really has, not only the tables.
 *
 * @since 6.1.8
 */
final class CustomAdminView extends Writer
{
	/**
	 * The View Registry.
	 *
	 * @var    View
	 * @since  6.1.8
	 */
	protected View $view;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.8
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.8
	 */
	protected Source $source;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.8
	 */
	protected Pairing $pairing;

	/**
	 * The Text Resolver.
	 *
	 * @var    Text
	 * @since  6.1.8
	 */
	protected Text $text;

	/**
	 * Constructor.
	 *
	 * @param   Config         $config    The extrusion configuration.
	 * @param   Resolved       $resolved  The resolved definition registry.
	 * @param   ItemInterface  $item      The JCB data item writer.
	 * @param   Report         $report    The run report registry.
	 * @param   View           $view      The classified view registry.
	 * @param   Guid           $guid      The identity resolver.
	 * @param   Source         $source    The source identity registry.
	 * @param   Pairing        $pairing   The pairing resolver.
	 * @param   Text           $text      The readable text resolver.
	 *
	 * @since   6.1.8
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		View $view,
		Guid $guid,
		Source $source,
		Pairing $pairing,
		Text $text
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->view = $view;
		$this->guid = $guid;
		$this->source = $source;
		$this->pairing = $pairing;
		$this->text = $text;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.8
	 */
	protected function table(): string
	{
		return 'custom_admin_view';
	}

	/**
	 * Write every custom admin view the reader recovered.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.8
	 */
	public function write(): int
	{
		if (!$this->config->get('admin', true))
		{
			return 0;
		}

		$written = 0;

		foreach ((array) $this->view->get('custom_admin_view') as $key => $entry)
		{
			$entry = (array) $entry;
			$name = (string) ($entry['name'] ?? $key);

			if ($name === '')
			{
				continue;
			}

			// the code itself says which folders belong to table views: an
			// editor beside the template, or a resolved view whose name the
			// folder answers to -- neither may ever become a custom admin view
			if (!empty($entry['crud']) || $this->dashboard($name) || $this->answered($name))
			{
				$this->report->set(
					'skipped.custom_admin_view.' . $this->key($name),
					'a table view answers for this template'
				);

				continue;
			}

			// whether the component names this screen in a menu or guards it
			// with a rule is a switch on its link, not proof that it exists,
			// so it is recorded rather than required
			if (!$this->named($name))
			{
				$this->report->set(
					'custom_admin_view.unnamed.' . $this->key($name),
					'the component names this screen in no menu and guards it '
					. 'with no rule of its own'
				);
			}

			if ($this->one($name, (string) $key, $entry))
			{
				$written++;
			}
		}

		if ($written > 0)
		{
			$this->report->set('counts.custom_admin_view', $written);
		}

		return $written;
	}

	/**
	 * Write one custom admin view.
	 *
	 * @param   string                $name   The view code name.
	 * @param   string                $key    The view's registry key.
	 * @param   array<string, mixed>  $entry  What the reader recovered.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.8
	 */
	protected function one(string $name, string $key, array $entry): bool
	{
		$guid = $this->pairing->guid(
			'custom_admin_view',
			$this->key($name),
			$this->guid->derive([$this->option(), 'custom_admin_view', $name])
		);

		if ($guid === null)
		{
			return false;
		}

		$readable = (string) ($entry['system_name'] ?? $this->text->humanise($name));

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $readable;
		$definition->codename = $name;
		$definition->system_name = $readable;
		$definition->description = (string) ($entry['description'] ?? $readable);
		$definition->default = $this->scaffold($readable);
		$definition->php_view = (string) ($entry['php_view'] ?? '');
		$definition->add_php_view = (int) ($entry['add_php_view'] ?? 0);
		$definition->main_get = (string) $this->resolved->get(
			'dynamic_get.custom_admin_view.' . $this->key($key) . '.guid',
			''
		);
		$definition->published = 1;

		// a view that already stands is a person's own work -- its names, its
		// body, its PHP, its description and its data source -- and a re-run
		// touches none of it: the compiled template is derived from that very
		// record, and nothing a source states about a custom screen outranks
		// what the person keeps in JCB. Only a new view is scaffolded
		if (!$this->store($definition, [
			'name', 'codename', 'system_name', 'description', 'default',
			'php_view', 'add_php_view', 'main_get', 'published'
		]))
		{
			return false;
		}

		$this->resolved->set('custom_admin_view.' . $this->key($name) . '.guid', $guid);
		$this->resolved->set('custom_admin_view.' . $this->key($name) . '.name', $name);

		return true;
	}

	/**
	 * Whether one screen is the component's own dashboard.
	 *
	 * The compiler writes the default dashboard into a folder named for the
	 * component itself, and JCB keeps that screen on the component record --
	 * its dashboard type and its dashboard -- never as a custom admin view.
	 *
	 * @param   string  $name  The folder's code name.
	 *
	 * @return  bool  True when the folder is the component's dashboard.
	 * @since   6.1.8
	 */
	protected function dashboard(string $name): bool
	{
		$code = strtolower(trim(str_replace('com_', '', $this->option())));

		return $code !== '' && strtolower(trim($name)) === $code;
	}

	/**
	 * Whether the component itself names one screen.
	 *
	 * @param   string  $name  The folder's code name.
	 *
	 * @return  bool  True when the component names it.
	 * @since   6.1.8
	 */
	protected function named(string $name): bool
	{
		$name = strtolower(trim($name));
		$menu = (array) $this->source->get('menu', []);
		$screens = (array) $this->source->get('access_screens', []);

		if (isset($menu[$name]) || !empty($screens[$name]))
		{
			return true;
		}

		// a component with neither access rules nor a menu states nothing
		// either way, and then the screen stands on its own evidence
		return $menu === [] && $screens === [];
	}

	/**
	 * Whether a resolved table view answers for this template's name.
	 *
	 * @param   string  $name  The recovered view's code name.
	 *
	 * @return  bool  True when an admin view of this run answers for it.
	 * @since   6.1.8
	 */
	protected function answered(string $name): bool
	{
		$name = Text::code($name);

		// the database is the ground truth for what the component already
		// has: a folder answering to any of its own admin views' real names
		// is that view's territory, whether or not this run resolved it
		if (in_array(
			$name,
			(array) $this->resolved->get('existing.admin_view_names', []),
			true
		))
		{
			return true;
		}

		foreach ($this->views() as $view)
		{
			$path = $this->path($view);
			$single = Text::code((string) $this->resolved->get($path . '.name_single_code', $view));
			$list = Text::code((string) $this->resolved->get($path . '.name_list_code', $single . 's'));

			if ($name === $single || $name === $list || $name === Text::code($view))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The body a recovered screen starts from.
	 *
	 * A screen's own markup is its author's and is never lifted out of the
	 * files a component builds it with, so a recovered screen needs a body of
	 * its own to be a screen at all. This is that body: it renders whatever the
	 * view's get returns, naming each value by the key it arrives under, and
	 * says plainly when the get returns nothing yet.
	 *
	 * Nothing here is particular to the component it came from, or to how that
	 * component happened to be built -- which is the point. A person replaces
	 * it with the screen they want; until then the screen compiles, opens, and
	 * shows what it has.
	 *
	 * @param   string  $readable  The view's human readable name.
	 *
	 * @return  string  The body to store.
	 * @since   6.1.8
	 */
	protected function scaffold(string $readable): string
	{
		$title = htmlspecialchars($readable, ENT_QUOTES, 'UTF-8');

		return <<<HTML
<div class="j-container">
	<h1>{$title}</h1>
	<?php if (!empty(\$this->items)) : ?>
		<table class="table table-striped">
			<tbody>
			<?php foreach (\$this->items as \$item) : ?>
				<tr>
					<?php foreach ((array) \$item as \$value) : ?>
						<td><?php echo \$this->escape((string) \$value); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php elseif (!empty(\$this->item)) : ?>
		<dl class="dl-horizontal">
			<?php foreach ((array) \$this->item as \$key => \$value) : ?>
				<dt><?php echo \$this->escape((string) \$key); ?></dt>
				<dd><?php echo \$this->escape((string) \$value); ?></dd>
			<?php endforeach; ?>
		</dl>
	<?php else : ?>
		<div class="alert alert-info">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php endif; ?>
</div>
HTML;
	}

	/**
	 * The component option, when it is known.
	 *
	 * @return  string  The com_ prefixed option, or an empty string.
	 * @since   6.1.8
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
