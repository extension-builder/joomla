<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    19th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use PHPUnit\Framework\Attributes\CoversNamespace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use stdClass;
use VDM\Joomla\Componentbuilder\Compiler\Architecture\CustomView\Body;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomAdminViewListId;
use VDM\Joomla\Componentbuilder\Compiler\Builder\CustomForm;
use VDM\Joomla\Componentbuilder\Compiler\Utilities\Placefix;


/**
 * Generated custom view body and form contracts across Joomla targets.
 *
 * @since  6.1.7
 */
#[CoversNamespace('VDM\Joomla\Componentbuilder\Compiler\Architecture')]
#[UsesNamespace('VDM\Joomla\Componentbuilder\Compiler')]
#[UsesNamespace('VDM\Joomla\Utilities')]
final class VersionedCustomViewTest extends ArchitectureTestCase
{
	/**
	 * The body of a view that reads one item, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BODY_PLAIN = <<<'GEN'

Hello there
GEN;

	/**
	 * The body of a paginated view that placed its own limit box, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BODY_LIMITBOX = <<<'GEN'
Top
<?php echo $this->pagination->getLimitBox(); ?>
Bottom

<?php if (isset($this->items) && isset($this->pagination) && isset($this->pagination->pagesTotal) && $this->pagination->pagesTotal > 1): ?>
	<div class="pagination">
		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
			<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> </p>
		<?php endif; ?>
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
<?php endif; ?>
GEN;

	/**
	 * The body of a paginated view that placed nothing itself, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_BODY_DEFAULT_PAGINATION = <<<'GEN'
A list with no marks

<?php if (isset($this->items) && isset($this->pagination) && isset($this->pagination->pagesTotal) && $this->pagination->pagesTotal > 1): ?>
	<div class="pagination">
		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
			<p class="counter pull-right"> <?php echo $this->pagination->getPagesCounter(); ?> <?php echo $this->pagination->getLimitBox(); ?></p>
		<?php endif; ?>
		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
<?php endif; ?>
GEN;

	/**
	 * The site form tag of every target before Joomla 6, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_SITE_SHARED = <<<'GEN'
<form action="<?php echo Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo'); ?>" method="post" name="adminForm" id="adminForm">

GEN;

	/**
	 * The site form tag of Joomla 6, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_SITE_J6 = <<<'GEN'
<form action="<?php echo Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php'); ?>" method="post" name="adminForm" id="adminForm">

GEN;

	/**
	 * The administrator form tag of a view reading a list, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_ADMIN_LIST = <<<'GEN'
<form action="<?php echo Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=demo'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

GEN;

	/**
	 * The administrator form tag of a view reading one item, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_ADMIN_ITEM = <<<'GEN'
<form action="<?php echo Joomla___d4c76099_4c32_408a_8701_d0a724484dfd___Power::_('index.php?option=com_demo&view=demo' . $urlId); ?>" method="post" name="adminForm" id="adminForm" class="form-validate" enctype="multipart/form-data">

GEN;

	/**
	 * The close of a form on a view that carries a list id, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_BOTTOM_WITH_ID = <<<'GEN'

	<input type="hidden" name="id" value="<?php echo $this->app->getInput()->getInt('id', 0); ?>" />
<input type="hidden" name="task" value="" />
<?php echo Html::_('form.token'); ?>
</form>
GEN;

	/**
	 * The close of a form, captured from the compiler.
	 *
	 * @var    string
	 * @since  6.1.7
	 */
	private const EXPECTED_FORM_BOTTOM = <<<'GEN'

<input type="hidden" name="task" value="" />
<?php echo Html::_('form.token'); ?>
</form>
GEN;

	/**
	 * The targets that carry the component in the query of a site form.
	 *
	 * @return  array<string, array{string}>
	 * @since   6.1.7
	 */
	public static function earlierVersions(): array
	{
		return [
			'Joomla 3' => ['JoomlaThree'],
			'Joomla 4' => ['JoomlaFour'],
			'Joomla 5' => ['JoomlaFive'],
		];
	}

	/**
	 * Build a custom view definition.
	 *
	 * @param   string  $default     What the view was drawn with.
	 * @param   int     $gettype     What its main get method returns.
	 * @param   int     $pagination  Whether it is paginated.
	 *
	 * @return  array
	 * @since   6.1.7
	 */
	private function view(string $default, int $gettype = 2, int $pagination = 1): array
	{
		$settings = new stdClass();
		$settings->code = 'demo';
		$settings->default = $default;
		$settings->main_get = (object) ['gettype' => $gettype, 'pagination' => $pagination];

		return ['settings' => $settings];
	}

	/**
	 * Build the body writer.
	 *
	 * @return  Body
	 * @since   6.1.7
	 */
	private function body(): Body
	{
		$forms = new CustomForm();
		$forms->set('admin.demo', true);

		return $this->renderer(Body::class, ['customform' => $forms]);
	}

	/**
	 * Build the form writer of a target.
	 *
	 * @param   string  $version  Target namespace segment.
	 * @param   bool    $hasForm  Whether the view carries a form.
	 * @param   bool    $hasId    Whether the view carries a list id.
	 *
	 * @return  object
	 * @since   6.1.7
	 */
	private function form(string $version, bool $hasForm = true, bool $hasId = false): object
	{
		$forms = new CustomForm();
		if ($hasForm)
		{
			$forms->set($this->config()->build_target . '.demo', true);
		}

		$ids = new CustomAdminViewListId();
		if ($hasId)
		{
			$ids->set('demo', true);
		}

		return $this->renderer(
			$this->targetClass($version, 'CustomView\\Form', ['JoomlaSix']),
			['customform' => $forms, 'customadminviewlistid' => $ids]
		);
	}

	/**
	 * A view drawn with nothing is given no body.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewDrawnWithNothingIsGivenNoBody(): void
	{
		$view = $this->view('');

		$this->assertSame('', $this->body()->get($view));
	}

	/**
	 * A view that reads one item is given what it was drawn with.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewReadingOneItemIsGivenWhatItWasDrawnWith(): void
	{
		$view = $this->view('Hello there', 1, 0);

		$this->assertSame(self::EXPECTED_BODY_PLAIN, $this->body()->get($view));
	}

	/**
	 * A paginated view that placed its own limit box keeps it where it put it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewThatPlacedItsOwnLimitBoxKeepsIt(): void
	{
		$view = $this->view("Top\n" . Placefix::_('LIMITBOX') . "\nBottom");

		$this->assertSame(self::EXPECTED_BODY_LIMITBOX, $this->body()->get($view));
	}

	/**
	 * A paginated view that placed nothing is given the pagination the
	 * compiler builds for it.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAPaginatedViewThatPlacedNothingIsGivenTheDefault(): void
	{
		$view = $this->view('A list with no marks');

		$this->assertSame(
			self::EXPECTED_BODY_DEFAULT_PAGINATION, $this->body()->get($view)
		);
	}

	/**
	 * A view that carries no form is wrapped in none.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithoutAFormIsWrappedInNone(): void
	{
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			'', $this->form('JoomlaSix', false)->get($view, $gettype, 1)
		);
	}

	/**
	 * A site form of an earlier target names the component it posts to.
	 *
	 * @param   string  $version  Target namespace segment.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	#[DataProvider('earlierVersions')]
	public function testASiteFormOfAnEarlierTargetNamesItsComponent(string $version): void
	{
		$this->config()->set('build_target', 'site');
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			self::EXPECTED_FORM_SITE_SHARED, $this->form($version)->get($view, $gettype, 1)
		);
	}

	/**
	 * A Joomla 6 site form posts to index.php on its own.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAJoomlaSixSiteFormPostsToIndexOnItsOwn(): void
	{
		$this->config()->set('build_target', 'site');
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			self::EXPECTED_FORM_SITE_J6, $this->form('JoomlaSix')->get($view, $gettype, 1)
		);
	}

	/**
	 * An administrator form of a view reading a list posts to the view itself.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdministratorListFormPostsToTheView(): void
	{
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			self::EXPECTED_FORM_ADMIN_LIST, $this->form('JoomlaSix')->get($view, $gettype, 1)
		);
	}

	/**
	 * An administrator form of a view reading one item carries the id it was
	 * reached with.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAnAdministratorItemFormCarriesTheIdItWasReachedWith(): void
	{
		$view = 'demo';
		$gettype = 1;

		$this->assertSame(
			self::EXPECTED_FORM_ADMIN_ITEM, $this->form('JoomlaSix')->get($view, $gettype, 1)
		);
	}

	/**
	 * A form closes with its task and token.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAFormClosesWithItsTaskAndToken(): void
	{
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			self::EXPECTED_FORM_BOTTOM, $this->form('JoomlaSix')->get($view, $gettype, 2)
		);
	}

	/**
	 * A view that carries a list id closes with that id too.
	 *
	 * @return  void
	 * @since   6.1.7
	 */
	public function testAViewWithAListIdClosesWithIt(): void
	{
		$view = 'demo';
		$gettype = 2;

		$this->assertSame(
			self::EXPECTED_FORM_BOTTOM_WITH_ID,
			$this->form('JoomlaSix', true, true)->get($view, $gettype, 2)
		);
	}
}
