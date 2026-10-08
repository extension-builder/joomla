<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    7th October, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Tests\Componentbuilder\Compiler\Architecture;


use Joomla\Input\Input;
use Joomla\CMS\Form\Form;


/**
 * Request, table and encryption boundaries for executing generated save blocks.
 *
 * @since  6.2.0
 */
final class GeneratedSaveModelFixture
{
	/**
	 * The form retained by getForm and subsequently mutated by validation plugins.
	 *
	 * @var    Form|null
	 * @since  6.2.0
	 */
	public ?Form $form = null;

	/**
	 * The submitted request data and method.
	 *
	 * @var    Input
	 * @since  6.2.0
	 */
	public Input $input;

	/**
	 * The raw database row, unchanged by a model's getItem transformations.
	 *
	 * @var    array
	 * @since  6.2.0
	 */
	public array $stored;

	/**
	 * Whether this represents the API client.
	 *
	 * @var    bool
	 * @since  6.2.0
	 */
	public bool $api = true;

	/**
	 * Whether loading the stored row succeeds.
	 *
	 * @var    bool
	 * @since  6.2.0
	 */
	public bool $loadSucceeds = true;

	/**
	 * Number of database loads requested by generated code.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	public int $loads = 0;

	/**
	 * Number of encryption calls requested by generated code.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	public int $encryptions = 0;

	/**
	 * Number of expert initializer calls requested by generated code.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	public int $expertInitializers = 0;

	/**
	 * Number of expert field conversions requested by generated code.
	 *
	 * @var    int
	 * @since  6.2.0
	 */
	public int $expertConversions = 0;

	/**
	 * The error recorded by a failed model save.
	 *
	 * @var    string|null
	 * @since  6.2.0
	 */
	public ?string $error = null;

	/**
	 * Configure one isolated save request.
	 *
	 * @param   array   $stored     Raw stored column values.
	 * @param   array   $submitted  Explicit request fields.
	 * @param   string  $method     HTTP method.
	 *
	 * @since   6.2.0
	 */
	public function __construct(array $stored, array $submitted, string $method = 'PATCH')
	{
		$this->stored = $stored;
		$this->input = new Input(['data' => $submitted]);
		$this->input->server->set('REQUEST_METHOD', $method);
	}

	/**
	 * Resolve the validation form at the native model-state boundary.
	 *
	 * @param   string  $key      State key.
	 * @param   mixed   $default  Missing-value fallback.
	 *
	 * @return  mixed
	 * @since   6.2.0
	 */
	public function getState(string $key, mixed $default = null): mixed
	{
		return $key === 'jcb.api.patch.form' ? $this->form : $default;
	}

	/**
	 * Supply the application at the generated Factory boundary.
	 *
	 * @return  self
	 * @since   6.2.0
	 */
	public function getApplication(): self
	{
		return $this;
	}

	/**
	 * Identify whether this request belongs to the API.
	 *
	 * @param   string  $client  Application client name.
	 *
	 * @return  bool
	 * @since   6.2.0
	 */
	public function isClient(string $client): bool
	{
		return $client === 'api' && $this->api;
	}

	/**
	 * Supply a database-table boundary that exposes stored columns only.
	 *
	 * @return  object
	 * @since   6.2.0
	 */
	public function getTable(): object
	{
		return new class($this)
		{
			/**
			 * The fixture owning the stored row and load results.
			 *
			 * @var    GeneratedSaveModelFixture
			 * @since  6.2.0
			 */
			private GeneratedSaveModelFixture $owner;

			/**
			 * Attach the current request fixture.
			 *
			 * @param   GeneratedSaveModelFixture  $owner  Request fixture.
			 *
			 * @since   6.2.0
			 */
			public function __construct(GeneratedSaveModelFixture $owner)
			{
				$this->owner = $owner;
			}

			/**
			 * Return one raw column, including an explicit null.
			 *
			 * @param   string  $name  Column name.
			 *
			 * @return  mixed
			 * @since   6.2.0
			 */
			public function __get(string $name): mixed
			{
				return $this->owner->stored[$name];
			}

			/**
			 * Expose database column metadata in Joomla's shape.
			 *
			 * @return  array
			 * @since   6.2.0
			 */
			public function getFields(): array
			{
				return array_map(
					static fn (string $name): object => (object) ['Field' => $name],
					array_keys($this->owner->stored)
				);
			}

			/**
			 * Identify the primary key column.
			 *
			 * @return  string
			 * @since   6.2.0
			 */
			public function getKeyName(): string
			{
				return 'id';
			}

			/**
			 * Record an attempted row load without a database connection.
			 *
			 * @param   int  $id  Record identifier.
			 *
			 * @return  bool
			 * @since   6.2.0
			 */
			public function load(int $id): bool
			{
				$this->owner->loads++;

				return $this->owner->loadSucceeds && $id === $this->owner->stored['id'];
			}
		};
	}

	/**
	 * Record a save error in place of Joomla's model error stack.
	 *
	 * @param   string  $error  Error message.
	 *
	 * @return  void
	 * @since   6.2.0
	 */
	public function setError(string $error): void
	{
		$this->error = $error;
	}

	/**
	 * Resolve a language key without an installed language service.
	 *
	 * @param   string  $key  Language key.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	public function translate(string $key): string
	{
		return $key;
	}

	/**
	 * Record the encryption boundary without invoking a real cipher.
	 *
	 * @param   string  $value  Plaintext input.
	 *
	 * @return  string
	 * @since   6.2.0
	 */
	public function encryptString(string $value): string
	{
		$this->encryptions++;

		return 'encrypted:' . $value;
	}
}
