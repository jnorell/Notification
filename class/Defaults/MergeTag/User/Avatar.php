<?php
/**
 * Avatar merge tag class
 *
 * Requirements:
 * - Trigger property `user_object` or any other passed as
 * `property_name` parameter. Must be an object with a `user_email`
 * property, preferabely WP_User.
 *
 * @package notification
 */

namespace BracketSpace\Notification\Defaults\MergeTag\User;

use BracketSpace\Notification\Defaults\MergeTag\HtmlTag;

/**
 * Avatar merge tag class
 */
class Avatar extends HtmlTag {

	/**
	 * Trigger property to get the user data from
	 *
	 * @var string
	 */
	protected $property_name = 'user_object';

	/**
	 * Merge tag constructor
	 *
	 * @since [Next]
	 * @param array $params merge tag configuration params.
	 */
	public function __construct( $params = [] ) {

		if ( isset( $params['property_name'] ) && ! empty( $params['property_name'] ) ) {
			$this->property_name = $params['property_name'];
		}

		$args = wp_parse_args(
			$params,
			[
				'slug'        => 'user_avatar',
				'name'        => __( 'User avatar', 'notification' ),
				'description' => get_avatar( get_option( 'admin_email' ) ),
				'example'     => true,
				'resolver'    => function( $trigger ) {

					if ( isset( $trigger->{ $this->property_name }->user_email ) ) {
						return get_avatar( $trigger->{ $this->property_name }->user_email );
					}

					return '';
				},
				'group'       => __( 'User', 'notification' ),
			]
		);

		parent::__construct( $args );

	}

	/**
	 * Function for checking requirements
	 *
	 * @return boolean
	 */
	public function check_requirements() {
		return isset( $this->trigger->{ $this->property_name }->user_email );
	}

}
