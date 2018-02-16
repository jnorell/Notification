<?php
/**
 * Comment type merge tag
 *
 * @package notification
 */

namespace underDEV\Notification\Defaults\MergeTag\Comment;

use underDEV\Notification\Defaults\MergeTag\StringTag;


/**
 * Comment type merge tag class
 */
class CommentType extends StringTag {

	/**
	 * Constructor
	 *
	 * @param object $trigger Trigger object to access data from.
	 */
	public function __construct() {

		parent::__construct( array(
			'slug'        => 'comment_type',
			'name'        => __( 'Comment type' ),
			'description' => __( 'Comment. pingback, trackback' ),
			'example'     => true,
			'resolver'    => function() {
				return ( $this->trigger->comment->comment_type === '' ) ? __( 'comment' ) : $this->trigger->comment->comment_type;
			},
		) );

	}

	/**
	 * Function for checking requirements
	 *
	 * @return boolean
	 */
	public function check_requirements( ) {

		return isset( $this->trigger->comment->comment_type );

	}

}
