<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    2nd September, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\Api\Plugin;


use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Controller\RecordId;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\Api\Plugin\Routes;
use VDM\Joomla\Componentbuilder\Compiler\Builder\DatabaseUniqueKeys;
use VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture\ArchitectureTestCase;


/**
 * The route registration the linked web services plugin receives.
 *
 * @since 6.1.7
 */
#[CoversClass(Routes::class)]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Abstraction')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class RoutesTest extends ArchitectureTestCase
{
	/**
	 * The routes of a view with both resources, a guid and a unique alias.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BOTH = <<<'GEN'
// Register the JSON:API routes of com_demo.
		$defaults = ['component' => 'com_demo'];
		$getDefaults = ['public' => false, 'component' => 'com_demo'];

		// The routes of the articles resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/demo/articles', 'articles.displayList', [], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/demo/articles/:id', 'article.displayItem', ['id' => '(\d+)'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/demo/articles/guid/:guid', 'article.displayItem', ['guid' => '([0-9a-fA-F-]{36})'], $getDefaults),
			new \Joomla\Router\Route(['GET'], 'v1/demo/articles/alias/:alias', 'article.displayItem', ['alias' => '([^/]+)'], $getDefaults),
			new \Joomla\Router\Route(['POST'], 'v1/demo/articles', 'article.add', [], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/demo/articles/:id', 'article.edit', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/demo/articles/guid/:guid', 'article.edit', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['PATCH'], 'v1/demo/articles/alias/:alias', 'article.edit', ['alias' => '([^/]+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/demo/articles/:id', 'article.delete', ['id' => '(\d+)'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/demo/articles/guid/:guid', 'article.delete', ['guid' => '([0-9a-fA-F-]{36})'], $defaults),
			new \Joomla\Router\Route(['DELETE'], 'v1/demo/articles/alias/:alias', 'article.delete', ['alias' => '([^/]+)'], $defaults),
		]);
GEN;

	/**
	 * The whole method of a Joomla 5 or 6 plugin for a list-only view.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_EVENT_METHOD = <<<'GEN'
/**
	 * Register the JSON:API routes of com_demo.
	 *
	 * @param   \Joomla\CMS\Event\Application\BeforeApiRouteEvent  $event  The event.
	 *
	 * @return  void
	 *
	 * @since   5.0.0
	 */
	public function onBeforeApiRoute(\Joomla\CMS\Event\Application\BeforeApiRouteEvent $event): void
	{
		// Take the API router from the event.
		$router = $event->getRouter();

		// Register the JSON:API routes of com_demo.
		$defaults = ['component' => 'com_demo'];
		$getDefaults = ['public' => false, 'component' => 'com_demo'];

		// The routes of the authors resource.
		$router->addRoutes([
			new \Joomla\Router\Route(['GET'], 'v1/demo/authors', 'authors.displayList', [], $getDefaults),
		]);
	}
GEN;

	/**
	 * The whole method of a Joomla 4 plugin when no view has an API.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_LEGACY_METHOD = <<<'GEN'
/**
	 * Register the JSON:API routes of com_demo.
	 *
	 * @param   \Joomla\CMS\Router\ApiRouter  &$router  The API router.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onBeforeApiRoute(&$router): void
	{
		// No admin view of com_demo has an API.
	}
GEN;

	/**
	 * A component without API views registers nothing.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAComponentWithoutApiViewsRegistersNothing(): void
	{
		$subject = $this->subject();
		$none = '// No admin view of com_demo has an API.';

		$this->assertSame($none, $subject->get([]));
		$this->assertSame($none, $subject->get([$this->view('note', 'notes', 0)]));
		$this->assertSame($none, $subject->get([$this->view('note', 'notes', 7)]));
		$this->assertSame($none, $subject->get([['settings' => (object) []], 'not a view']));
	}

	/**
	 * A view with both resources gets the list route and every item route by id and by key.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithBothResourcesGetsEveryRoute(): void
	{
		$this->assertSame(self::EXPECTED_BOTH, $this->subject()->get([$this->view('article', 'articles', 2)]));
	}

	/**
	 * The list option gets the list route alone and the item option the item routes alone.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testTheListAndItemOptionsGetTheirOwnRoutes(): void
	{
		$subject = $this->subject();

		$list = $subject->get([$this->view('author', 'authors', 1)]);
		$item = $subject->get([$this->view('author', 'authors', 3)]);

		$this->assertStringContainsString("'v1/demo/authors', 'authors.displayList'", $list);
		$this->assertStringNotContainsString('author.displayItem', $list);
		$this->assertStringNotContainsString('author.add', $list);

		$this->assertStringNotContainsString('authors.displayList', $item);
		$this->assertStringContainsString("'v1/demo/authors/:id', 'author.displayItem'", $item);
		$this->assertStringContainsString("'v1/demo/authors', 'author.add'", $item);
		$this->assertStringContainsString("'v1/demo/authors/:id', 'author.edit'", $item);
		$this->assertStringContainsString("'v1/demo/authors/:id', 'author.delete'", $item);
		$this->assertStringNotContainsString('/guid/', $item);
	}

	/**
	 * Every view with an API gets its own registration in one body.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testEveryViewWithAnApiGetsItsOwnRegistration(): void
	{
		$code = $this->subject()->get([
			$this->view('article', 'articles', 2),
			$this->view('note', 'notes', 0),
			$this->view('author', 'authors', 1),
		]);

		$this->assertSame(2, substr_count($code, '$router->addRoutes(['));
		$this->assertSame(1, substr_count($code, "\$defaults = ['component' => 'com_demo'];"));
		$this->assertStringContainsString('// The routes of the articles resource.', $code);
		$this->assertStringContainsString('// The routes of the authors resource.', $code);
		$this->assertStringNotContainsString('notes', $code);
	}

	/**
	 * A view whose list or single name is null keeps the routes of the other.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAListOrSingleNameKeepsTheOtherRoutes(): void
	{
		$subject = $this->subject();

		$noList = $this->view('author', 'authors', 2);
		$noList['settings']->name_list = 'null';
		$noSingle = $this->view('author', 'authors', 2);
		$noSingle['settings']->name_single = 'null';

		$this->assertStringNotContainsString('displayList', $subject->get([$noList]));
		$this->assertStringContainsString('author.displayItem', $subject->get([$noList]));
		$this->assertStringContainsString('displayList', $subject->get([$noSingle]));
		$this->assertStringNotContainsString('author.displayItem', $subject->get([$noSingle]));
	}

	/**
	 * Below Joomla 4 no view has an API.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testBelowJoomlaFourNoViewHasAnApi(): void
	{
		$this->config()->set('joomla_version', 3);

		$this->assertSame(
			'// No admin view of com_demo has an API.',
			$this->subject()->get([$this->view('article', 'articles', 2)])
		);
	}

	/**
	 * Joomla 5 and up get the event method around the body.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaFiveAndUpGetTheEventMethod(): void
	{
		$this->assertSame(
			self::EXPECTED_EVENT_METHOD,
			$this->subject()->getMethod([$this->view('author', 'authors', 1)])
		);
	}

	/**
	 * Joomla 4 gets the legacy router argument.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testJoomlaFourGetsTheLegacyRouterArgument(): void
	{
		$this->config()->set('joomla_version', 4);

		$this->assertSame(self::EXPECTED_LEGACY_METHOD, $this->subject()->getMethod([]));
	}

	/**
	 * The placeholders are registered in both forms for the plugins.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testThePlaceholdersAreRegisteredInBothForms(): void
	{
		$subject = $this->subject();
		$views = [$this->view('article', 'articles', 2)];

		$subject->set($views);

		$placeholder = $this->placeholder();

		$this->assertSame($subject->get($views), $placeholder->get_('API_ROUTES'));
		$this->assertSame($subject->get($views), $placeholder->get_h('API_ROUTES'));
		$this->assertSame($subject->getMethod($views), $placeholder->get_('API_ROUTES_METHOD'));
		$this->assertSame($subject->getMethod($views), $placeholder->get_h('API_ROUTES_METHOD'));
		$this->assertSame($subject->get($views), $placeholder->update_('[[[API_ROUTES]]]'));
		$this->assertSame($subject->getMethod($views), $placeholder->update_('###API_ROUTES_METHOD###'));
	}

	/**
	 * The renderer with the article table carrying a guid and a unique alias.
	 *
	 * @return  Routes
	 * @since   6.1.7
	 */
	private function subject(): Routes
	{
		$keys = new DatabaseUniqueKeys();
		$keys->add('article', 'guid', true);
		$keys->add('article', 'alias', true);

		return $this->renderer(Routes::class, [
			'recordid' => $this->renderer(RecordId::class, ['databaseuniquekeys' => $keys]),
		]);
	}

	/**
	 * An admin view link as the component data carries it.
	 *
	 * @param   string  $single  The single code.
	 * @param   string  $list    The list code.
	 * @param   int     $api     The add_api option.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(string $single, string $list, int $api): array
	{
		return [
			'adminview' => 1,
			'add_api' => $api,
			'settings' => (object) [
				'name_single' => ucfirst($single),
				'name_list' => ucfirst($list),
				'name_single_code' => $single,
				'name_list_code' => $list,
			],
		];
	}
}
