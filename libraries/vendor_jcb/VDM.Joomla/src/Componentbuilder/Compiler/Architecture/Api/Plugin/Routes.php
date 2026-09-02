<?php
/**
 * @package    Joomla.Component.Builder
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin;


use VDM\Joomla\Componentbuilder\Compiler\Config;
use VDM\Joomla\Componentbuilder\Compiler\Placeholder;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Indent;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Line;


/**
 * The JSON:API route registration of the linked web services plugin
 *
 * The compiler does not generate a plugin. A plugin of the webservices
 * group that the JCB user creates and links to the component carries one
 * of these placeholders, which the compiler fills for every admin view
 * that has an API:
 *
 *   [[[API_ROUTES]]]         the body of the route method, indented two
 *                            tabs after its first line, and
 *   [[[API_ROUTES_METHOD]]]  the whole onBeforeApiRoute() method, indented
 *                            one tab after its first line;
 *
 * both are also accepted in their ###API_ROUTES### and
 * ###API_ROUTES_METHOD### form.
 *
 * @since 6.1.7
 */
class Routes
{
	/**
	 * The Config Class.
	 *
	 * @var   Config
	 * @since 6.1.7
	 */
	protected Config $config;

	/**
	 * The Placeholder Class.
	 *
	 * @var   Placeholder
	 * @since 6.1.7
	 */
	protected Placeholder $placeholder;

	/**
	 * The Api Controller RecordId Class.
	 *
	 * @var   RecordId
	 * @since 6.1.7
	 */
	protected RecordId $recordid;

	/**
	 * Constructor.
	 *
	 * @param Config       $config       The Config Class.
	 * @param Placeholder  $placeholder  The Placeholder Class.
	 * @param RecordId     $recordid     The Api Controller RecordId Class.
	 *
	 * @since 6.1.7
	 */
	public function __construct(Config $config, Placeholder $placeholder,
		RecordId $recordid)
	{
		$this->config = $config;
		$this->placeholder = $placeholder;
		$this->recordid = $recordid;
	}

	/**
	 * Register the route placeholders the linked plugins may carry.
	 *
	 * @param   array  $views  The admin views of the component, each an array
	 *                         with its link settings and the add_api option.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function set(array $views): void
	{
		$this->placeholder->set('API_ROUTES', $this->get($views));
		$this->placeholder->set('API_ROUTES_METHOD', $this->getMethod($views));
	}

	/**
	 * Get the body of the route method.
	 *
	 * The first line carries no indentation, so the placeholder sits where
	 * the body starts; every following line is indented as a method body.
	 *
	 * @param   array  $views  The admin views of the component.
	 *
	 * @return  string  The route registration code.
	 * @since   6.1.7
	 */
	public function get(array $views): string
	{
		return $this->render($this->body($views), 2);
	}

	/**
	 * Get the whole route method of the plugin.
	 *
	 * The first line carries no indentation, so the placeholder sits where
	 * the method starts; every following line is indented as a class member.
	 * Joomla 4 gets the legacy router argument, Joomla 5 and up the event.
	 *
	 * @param   array  $views  The admin views of the component.
	 *
	 * @return  string  The route method code.
	 * @since   6.1.7
	 */
	public function getMethod(array $views): string
	{
		$component = 'com_' . $this->config->component_code_name;
		$legacy = $this->config->get('joomla_version', 3) < 5;

		$rows = [];
		$rows[] = [0, "/**"];
		$rows[] = [0, " * Register the JSON:API routes of {$component}."];
		$rows[] = [0, " *"];

		if ($legacy)
		{
			$rows[] = [0, " * @param   \\Joomla\\CMS\\Router\\ApiRouter  &\$router  The API router."];
		}
		else
		{
			$rows[] = [0, " * @param   \\Joomla\\CMS\\Event\\Application\\BeforeApiRouteEvent  \$event  The event."];
		}

		$rows[] = [0, " *"];
		$rows[] = [0, " * @return  void"];
		$rows[] = [0, " *"];
		$rows[] = [0, " * @since   " . ($legacy ? '4.0.0' : '5.0.0')];
		$rows[] = [0, " */"];

		if ($legacy)
		{
			$rows[] = [0, "public function onBeforeApiRoute(&\$router): void"];
			$rows[] = [0, "{"];
		}
		else
		{
			$rows[] = [0, "public function onBeforeApiRoute(\\Joomla\\CMS\\Event\\Application\\BeforeApiRouteEvent \$event): void"];
			$rows[] = [0, "{"];
			$rows[] = [1, "//" . Line::_(__LINE__, __CLASS__)
				. " Take the API router from the event."];
			$rows[] = [1, "\$router = \$event->getRouter();"];
			$rows[] = null;
		}

		foreach ($this->body($views) as $row)
		{
			$rows[] = ($row === null) ? null : [$row[0] + 1, $row[1]];
		}

		$rows[] = [0, "}"];

		return $this->render($rows, 1);
	}

	/**
	 * The rows of the route method body.
	 *
	 * @param   array  $views  The admin views of the component.
	 *
	 * @return  array  The rows, each an indentation level and a line, a
	 *                 blank line being null.
	 * @since   6.1.7
	 */
	private function body(array $views): array
	{
		$component = 'com_' . $this->config->component_code_name;
		$resources = [];

		foreach ($views as $view)
		{
			$resource = $this->resource(is_array($view) ? $view : []);

			if ($resource !== [])
			{
				$resources[] = $resource;
			}
		}

		if ($resources === [])
		{
			return [[0, "//" . Line::_(__LINE__, __CLASS__)
				. " No admin view of {$component} has an API."]];
		}

		$rows = [];
		$rows[] = [0, "//" . Line::_(__LINE__, __CLASS__)
			. " Register the JSON:API routes of {$component}."];
		$rows[] = [0, "\$defaults = ['component' => '{$component}'];"];
		$rows[] = [0, "\$getDefaults = ['public' => false, 'component' => '{$component}'];"];

		foreach ($resources as $resource)
		{
			$rows[] = null;

			foreach ($resource as $row)
			{
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * The rows registering the routes of one admin view.
	 *
	 * The list resource answers on the list code, the item resource on the
	 * single code, and both share the list code as the resource path, which
	 * is the JSON:API type the generated controllers declare.
	 *
	 * @param   array  $view  The admin view link with its settings.
	 *
	 * @return  array  The rows, none when the view has no API.
	 * @since   6.1.7
	 */
	private function resource(array $view): array
	{
		$api = $this->api($view);

		if ($api === 0 || !isset($view['settings']) || !is_object($view['settings']))
		{
			return [];
		}

		$settings = $view['settings'];
		$single = (string) ($settings->name_single_code ?? '');
		$list = (string) ($settings->name_list_code ?? '');

		if ($list === '')
		{
			return [];
		}

		$path = 'v1/' . $this->config->component_code_name . '/' . $list;
		$hasList = $api !== 3 && ($settings->name_list ?? 'null') != 'null';
		$hasItem = $api !== 1 && ($settings->name_single ?? 'null') != 'null'
			&& $single !== '';

		if (!$hasList && !$hasItem)
		{
			return [];
		}

		$routes = [];

		if ($hasList)
		{
			$routes[] = $this->route('GET', $path, $list . '.displayList', null, true);
		}

		if ($hasItem)
		{
			$keys = $this->recordid->keys($single);

			$routes[] = $this->route('GET', $path . '/:id', $single . '.displayItem', 'id', true);

			foreach ($keys as $key)
			{
				$routes[] = $this->route('GET', $path . '/' . $key . '/:' . $key,
					$single . '.displayItem', $key, true);
			}

			$routes[] = $this->route('POST', $path, $single . '.add', null, false);
			$routes[] = $this->route('PATCH', $path . '/:id', $single . '.edit', 'id', false);

			foreach ($keys as $key)
			{
				$routes[] = $this->route('PATCH', $path . '/' . $key . '/:' . $key,
					$single . '.edit', $key, false);
			}

			$routes[] = $this->route('DELETE', $path . '/:id', $single . '.delete', 'id', false);

			foreach ($keys as $key)
			{
				$routes[] = $this->route('DELETE', $path . '/' . $key . '/:' . $key,
					$single . '.delete', $key, false);
			}
		}

		$rows = [];
		$rows[] = [0, "//" . Line::_(__LINE__, __CLASS__)
			. " The routes of the {$list} resource."];
		$rows[] = [0, "\$router->addRoutes(["];

		foreach ($routes as $route)
		{
			$rows[] = [1, $route];
		}

		$rows[] = [0, "]);"];

		return $rows;
	}

	/**
	 * One route construction.
	 *
	 * @param   string       $method  The HTTP method.
	 * @param   string       $path    The route pattern.
	 * @param   string       $task    The controller and task.
	 * @param   string|null  $key     The route variable, when the pattern has one.
	 * @param   bool         $read    Whether the route reads, and so takes the GET defaults.
	 *
	 * @return  string  The route construction code.
	 * @since   6.1.7
	 */
	private function route(string $method, string $path, string $task,
		?string $key, bool $read): string
	{
		$rules = '[]';

		if ($key !== null)
		{
			$rules = "['{$key}' => '" . $this->rule($key) . "']";
		}

		return "new \\Joomla\\Router\\Route(['{$method}'], '{$path}', '{$task}', "
			. $rules . ", " . ($read ? '$getDefaults' : '$defaults') . "),";
	}

	/**
	 * The pattern of a route variable.
	 *
	 * The primary key is digits as in the core CRUD routes, the guid takes
	 * its 36 characters, any other unique key one path segment.
	 *
	 * @param   string  $key  The route variable.
	 *
	 * @return  string  The pattern.
	 * @since   6.1.7
	 */
	private function rule(string $key): string
	{
		if ($key === 'id')
		{
			return '(\\d+)';
		}

		if ($key === 'guid')
		{
			return '([0-9a-fA-F-]{36})';
		}

		return '([^/]+)';
	}

	/**
	 * The API option of an admin view link.
	 *
	 * @param   array  $view  The admin view link.
	 *
	 * @return  int  0 none, 1 list, 2 both, 3 item; 0 below Joomla 4.
	 * @since   6.1.7
	 */
	private function api(array $view): int
	{
		if ($this->config->get('joomla_version', 3) < 4 || !isset($view['add_api']))
		{
			return 0;
		}

		$api = (int) $view['add_api'];

		return in_array($api, [1, 2, 3], true) ? $api : 0;
	}

	/**
	 * Render rows as code whose first line carries no indentation.
	 *
	 * @param   array  $rows  The rows, each an indentation level and a line,
	 *                        a blank line being null.
	 * @param   int    $base  The indentation of the rows at level zero.
	 *
	 * @return  string  The code.
	 * @since   6.1.7
	 */
	private function render(array $rows, int $base): string
	{
		$code = '';
		$first = true;

		foreach ($rows as $row)
		{
			if ($row === null)
			{
				$code .= PHP_EOL;
				continue;
			}

			$code .= ($first ? '' : PHP_EOL . Indent::_($base + $row[0])) . $row[1];
			$first = false;
		}

		return $code;
	}
}
