<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    17th August, 2026
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
use VDM\Joomla\Interfaces\Data\ItemInterface;


/**
 * Writes the front end views a component's site templates describe.
 *
 * A site view is the front end counterpart of an admin view, but it is not built
 * from a database table: its body is the default template of its own folder. That
 * makes it recoverable from any component with a site folder, whether or not JCB
 * built it and whether or not the run ever saw a schema.
 *
 * The view's data source is the dynamic get the run wrote for it: a real back
 * end source when an admin view of this run answers for the view's name, and a
 * custom-get scaffold when none does -- either way main_get names it, because a
 * site view without a source displays nothing at all.
 *
 * @since 6.1.6
 */
final class SiteView extends Writer
{
	/**
	 * The View Registry.
	 *
	 * @var    View
	 * @since  6.1.6
	 */
	protected View $view;

	/**
	 * The Guid Resolver.
	 *
	 * @var    Guid
	 * @since  6.1.6
	 */
	protected Guid $guid;

	/**
	 * The Source Registry.
	 *
	 * @var    Source
	 * @since  6.1.6
	 */
	protected Source $source;

	/**
	 * The Pairing Resolver.
	 *
	 * @var    Pairing
	 * @since  6.1.7
	 */
	protected Pairing $pairing;

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
	 *
	 * @since   6.1.6
	 */
	public function __construct(
		Config $config,
		Resolved $resolved,
		ItemInterface $item,
		Report $report,
		View $view,
		Guid $guid,
		Source $source,
		Pairing $pairing
	)
	{
		parent::__construct($config, $resolved, $item, $report);

		$this->view = $view;
		$this->guid = $guid;
		$this->source = $source;
		$this->pairing = $pairing;
	}

	/**
	 * The JCB table this writer persists into.
	 *
	 * @return  string  The table name without its prefix.
	 * @since   6.1.6
	 */
	protected function table(): string
	{
		return 'site_view';
	}

	/**
	 * Write every site view the reader recovered.
	 *
	 * @return  int  The number of definitions written.
	 * @since   6.1.6
	 */
	public function write(): int
	{
		if (!$this->config->get('siteViews', true))
		{
			return 0;
		}

		$written = 0;

		foreach ((array) $this->view->get('site_view') as $key => $entry)
		{
			$entry = (array) $entry;
			$name = (string) ($entry['name'] ?? $key);

			if ($name === '')
			{
				continue;
			}

			if ($this->one($name, (string) $key, $entry))
			{
				$written++;
			}
		}

		$this->report->set('counts.site_view', $written);

		return $written;
	}

	/**
	 * Write one site view.
	 *
	 * @param   string                $name   The view code name.
	 * @param   string                $key    The view's registry key.
	 * @param   array<string, mixed>  $entry  What the reader recovered.
	 *
	 * @return  bool  True when the definition was written.
	 * @since   6.1.6
	 */
	protected function one(string $name, string $key, array $entry): bool
	{
		$guid = $this->pairing->guid(
			'site_view',
			$this->key($name),
			$this->guid->derive([$this->option(), 'site_view', $name])
		);

		if ($guid === null)
		{
			return false;
		}

		$readable = (string) ($entry['system_name'] ?? $name);

		$definition = new \stdClass();
		$definition->guid = $guid;
		$definition->name = $name;
		$definition->codename = $name;
		$definition->context = (string) ($entry['context'] ?? $name);
		$definition->system_name = $readable;
		$definition->description = (string) ($entry['description'] ?? $readable);
		$definition->default = $this->scaffold($readable);
		$definition->php_view = (string) ($entry['php_view'] ?? '');
		$definition->add_php_view = (int) ($entry['add_php_view'] ?? 0);
		$definition->main_get = (string) $this->resolved->get(
			'dynamic_get.site_view.' . $this->key($key) . '.guid',
			''
		);
		$definition->published = 1;

		// the source states the view's names and its body; its description
		// and its data source are scaffolding, so a view someone has since
		// pointed at a dynamic get of their own keeps it
		if (!$this->store($definition, ['description', 'main_get', 'published']))
		{
			return false;
		}

		$this->resolved->set('site_view.' . $this->key($name) . '.guid', $guid);
		$this->resolved->set('site_view.' . $this->key($name) . '.name', $name);

		return true;
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
	 * @since   6.1.6
	 */
	protected function option(): string
	{
		return (string) $this->source->get('code_name', '');
	}
}
