<?php
/**
 * Trigger abstract class
 *
 * @package notification
 */

namespace BracketSpace\Notification\Abstracts;

use BracketSpace\Notification\Interfaces;
use BracketSpace\Notification\Interfaces\Sendable;
use BracketSpace\Notification\Admin\FieldsResolver;
use BracketSpace\Notification\Defaults\Store\Notification as NotificationStore;

/**
 * Trigger abstract class
 */
abstract class Trigger extends Common implements Interfaces\Triggerable {

	/**
	 * Storage for Trigger's Carriers
	 *
	 * @var array
	 */
	private $carrier_storage = [];

	/**
	 * Group
	 *
	 * @var string
	 */
	protected $group = '';

	/**
	 * Short description of the Trigger
	 * No html tags allowed. Keep it tweet-short.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Flag indicating that trigger
	 * has been stopped
	 *
	 * @var boolean
	 */
	protected $stopped = false;

	/**
	 * Flag indicating that action
	 * has been postponed
	 *
	 * @var boolean
	 */
	protected $postponed = false;

	/**
	 * Bound actions
	 *
	 * @var array
	 */
	protected $actions = [];

	/**
	 * Merge tags
	 *
	 * @var array
	 */
	protected $merge_tags = [];

	/**
	 * Action's callback args
	 *
	 * @var array
	 */
	protected $callback_args = [];

	/**
	 * Trigger constructor
	 *
	 * @param string $slug slug.
	 * @param string $name nice name.
	 */
	public function __construct( $slug, $name ) {

		$this->slug = $slug;
		$this->name = $name;

		$this->merge_tags();

	}

	/**
	 * Used to register trigger merge tags
	 * Uses $this->add_merge_tag();
	 *
	 * @return void
	 */
	abstract public function merge_tags();

	/**
	 * Listens to an action
	 * This method just calls WordPress' add_action function,
	 * but it hooks the class' action method
	 *
	 * @param string  $tag           action hook.
	 * @param integer $priority      action priority, default 10.
	 * @param integer $accepted_args how many args the action accepts, default 1.
	 */
	public function add_action( $tag, $priority = 10, $accepted_args = 1 ) {

		if ( empty( $tag ) ) {
			trigger_error( 'Action tag cannot be empty', E_USER_ERROR );
		}

		array_push( $this->actions, [
			'tag'           => $tag,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		] );

		add_action( $tag, [ $this, '_action' ], $priority, $accepted_args );

	}

	/**
	 * Removes the action from the actions library.
	 *
	 * @param string  $tag           action hook.
	 * @param integer $priority      action priority, default 10.
	 * @param integer $accepted_args how many args the action accepts, default 1.
	 */
	public function remove_action( $tag, $priority = 10, $accepted_args = 1 ) {

		if ( empty( $tag ) ) {
			trigger_error( 'Action tag cannot be empty', E_USER_ERROR );
		}

		foreach ( $this->actions as $action_index => $action ) {
			if ( $action['tag'] === $tag && $action['priority'] === $priority && $action['accepted_args'] === $accepted_args ) {
				unset( $this->actions[ $action_index ] );
				break;
			}
		}

		remove_action( $tag, [ $this, '_action' ], $priority, $accepted_args );

	}

	/**
	 * Postpones the action with later hook
	 * It automatically stops the execution
	 *
	 * @param string  $tag           action hook.
	 * @param integer $priority      action priority, default 10.
	 * @param integer $accepted_args how many args the action accepts, default 1.
	 */
	public function postpone_action( $tag, $priority = 10, $accepted_args = 1 ) {

		$this->add_action( $tag, $priority, $accepted_args );

		$this->stopped   = true;
		$this->postponed = true;

	}

	/**
	 * Attaches the Carrier
	 *
	 * @param  Sendable $carrier Carrier class.
	 * @return void
	 */
	public function attach( Sendable $carrier ) {
		$this->carrier_storage[ $carrier->hash() ] = clone $carrier;
	}

	/**
	 * Gets attached Carriers
	 *
	 * @return array
	 */
	public function get_carriers() {
		return $this->carrier_storage;
	}

	/**
	 * Check if Trigger has attached Carriers
	 *
	 * @return array
	 */
	public function has_carriers() {
		return ! empty( $this->get_carriers() );
	}

	/**
	 * Detaches the Carrier
	 *
	 * @param  Sendable $carrier Carrier class.
	 * @return void
	 */
	public function detach( Sendable $carrier ) {
		if ( isset( $this->carrier_storage[ $carrier->hash() ] ) ) {
			unset( $this->carrier_storage[ $carrier->hash() ] );
		}
	}

	/**
	 * Detaches all the Carriers
	 *
	 * @return $this
	 */
	public function detach_all() {
		$this->carrier_storage = [];
		return $this;
	}

	/**
	 * Rolls out all the Carriers
	 *
	 * @return void
	 */
	public function roll_out() {

		foreach ( $this->get_carriers() as $carrier ) {
			$carrier->prepare_data();

			do_action_deprecated( 'notification/notification/pre-send', [
				$carrier,
				$this,
			], '[Next]', 'notification/carrier/pre-send' );

			do_action( 'notification/carrier/pre-send', $carrier, $this );

			if ( ! $carrier->is_suppressed() ) {

				$carrier->send( $this );

				do_action_deprecated( 'notification/notification/sent', [
					$carrier,
					$this,
				], '[Next]', 'notification/carrier/sent' );

				do_action( 'notification/carrier/sent', $carrier, $this );

			}
		}

	}

	/**
	 * Gets description
	 *
	 * @return string description
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets description
	 *
	 * @param string $description description.
	 * @return $this
	 */
	public function set_description( $description ) {
		$this->description = sanitize_text_field( $description );
		return $this;
	}

	/**
	 * Gets group
	 *
	 * @return string group
	 */
	public function get_group() {
		return $this->group;
	}

	/**
	 * Sets group
	 *
	 * @param string $group group.
	 * @return $this
	 */
	public function set_group( $group ) {
		$this->group = sanitize_text_field( $group );
		return $this;
	}

	/**
	 * Adds Trigger's Merge Tag
	 *
	 * @param Interfaces\Taggable $merge_tag merge tag object.
	 * @return $this
	 */
	public function add_merge_tag( Interfaces\Taggable $merge_tag ) {
		$merge_tag->set_trigger( $this );
		array_push( $this->merge_tags, $merge_tag );
		return $this;
	}

	/**
	 * Quickly adds new Merge Tag
	 *
	 * @since [Next]
	 * @param string $property_name Trigger property name.
	 * @param string $label         Nice, translatable Merge Tag label.
	 * @param string $group         Optional, translatable group name.
	 */
	public function add_quick_merge_tag( $property_name, $label, $group = null ) {
		return $this->add_merge_tag( new \BracketSpace\Notification\Defaults\MergeTag\StringTag( [
			'slug'     => $property_name,
			'name'     => $label,
			'group'    => $group,
			'resolver' => function( $trigger ) use ( $property_name ) {
				return $trigger->{ $property_name };
			},
		] ) );
	}

	/**
	 * Removes Trigger's merge tag
	 *
	 * @param string $merge_tag_slug Merge Tag slug.
	 * @return $this
	 */
	public function remove_merge_tag( $merge_tag_slug ) {

		foreach ( $this->merge_tags as $index => $merge_tag ) {
			if ( $merge_tag->get_slug() === $merge_tag_slug ) {
				unset( $this->merge_tags[ $index ] );
				break;
			}
		}

		return $this;

	}

	/**
	 * Gets Trigger's merge tags
	 *
	 * @since [Next] Added param $grouped which makes the array associative
	 *               with merge tag slugs as keys.
	 * @param string $type    Optional, all|visible|hidden, default: all.
	 * @param bool   $grouped Optional, default: false.
	 * @return $array merge tags
	 */
	public function get_merge_tags( $type = 'all', $grouped = false ) {

		if ( 'all' === $type ) {
			$tags = $this->merge_tags;
		} else {
			$tags = [];

			foreach ( $this->merge_tags as $merge_tag ) {
				if ( 'visible' === $type && ! $merge_tag->is_hidden() ) {
					array_push( $tags, $merge_tag );
				} elseif ( 'hidden' === $type && $merge_tag->is_hidden() ) {
					array_push( $tags, $merge_tag );
				}
			}
		}

		// Group the tags if needed.
		if ( $grouped ) {
			$grouped_tags = [];
			foreach ( $tags as $merge_tag ) {
				$grouped_tags[ $merge_tag->get_slug() ] = $merge_tag;
			}
			return $grouped_tags;
		}

		return $tags;

	}

	/**
	 * Resolves all Carrier fields with Merge Tags
	 *
	 * @since [Next] Fields resolving has been moved to additional API
	 *               which is called by the Carrier itself
	 * @return void
	 */
	private function resolve_fields() {

		foreach ( $this->get_carriers() as $carrier ) {
			$carrier->resolve_fields( $this );
		}

	}

	/**
	 * Cleans the Merge Tags
	 *
	 * @since 5.2.2
	 * @return void
	 */
	private function clean_merge_tags() {

		foreach ( $this->get_merge_tags() as $merge_tag ) {
			$merge_tag->clean_value();
		}

	}

	/**
	 * Attaches the Carriers to Trigger
	 *
	 * @return void
	 */
	public function set_carriers() {

		$store = new NotificationStore();

		foreach ( $store->with_trigger( $this->get_slug() ) as $notification ) {
			foreach ( $notification->get_carriers() as $carrier ) {
				if ( $carrier->is_enabled() ) {
					$carrier->notification = $notification;
					$this->attach( $carrier );
				}
			}
		}

	}

	/**
	 * Checks if trigger has been stopped
	 *
	 * @return boolean
	 */
	public function is_stopped() {
		return $this->stopped;
	}

	/**
	 * Checks if action has been postponed
	 *
	 * @return boolean
	 */
	public function is_postponed() {
		return $this->postponed;
	}

	/**
	 * Action callback
	 *
	 * @return void
	 */
	public function _action() {

		$this->detach_all()->set_carriers();

		// If no Carriers use this Trigger, bail.
		if ( ! $this->has_carriers() ) {
			return;
		}

		// reset the state.
		$this->stopped = false;

		// setup the arguments.
		$this->callback_args = func_get_args();

		// call the action.
		if ( $this->is_postponed() && method_exists( $this, 'postponed_action' ) ) {
			$result = call_user_func_array( [ $this, 'postponed_action' ], $this->callback_args );
		} elseif ( ! $this->is_postponed() && method_exists( $this, 'action' ) ) {
			$result = call_user_func_array( [ $this, 'action' ], $this->callback_args );
		} else {
			$result = true;
		}

		if ( false === $result ) {
			$this->stopped = true;
		}

		do_action( 'notification/trigger/action/did', $this );

		if ( $this->is_stopped() ) {
			return;
		}

		$this->resolve_fields();
		$this->roll_out();
		$this->clean_merge_tags();

	}

}
