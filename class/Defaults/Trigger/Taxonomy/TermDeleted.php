<?php
/**
 * Taxonomy term deleted trigger
 *
 * @package notification
 */

namespace BracketSpace\Notification\Defaults\Trigger\Taxonomy;

use BracketSpace\Notification\Defaults\MergeTag;

/**
 * Taxonomy term deleted trigger class
 */
class TermDeleted extends TermTrigger {

	/**
	 * Term object
	 *
	 * @var object
	 */
	public $term;

	/**
	 * Taxonomy slug
	 *
	 * @var string
	 */
	public $taxonomy;

	/**
	 * Constructor
	 *
	 * @param string $taxonomy optional, default: category.
	 */
	public function __construct( $taxonomy = 'category' ) {

		$this->taxonomy = $taxonomy;

		parent::__construct( array(
			'taxonomy' => $taxonomy,
			'slug'      => 'wordpress/' . $taxonomy . '/deleted',
			'name'      => sprintf( __( '%s term deleted', 'notification' ), parent::get_taxonomy_singular_name( $taxonomy ) ),
		) );

		$this->add_action( 'pre_delete_term', 100, 4 );

		// translators: 1. taxonomy name, 2. taxonomy slug.
		$this->set_description( sprintf( __( 'Fires when %s (%s) is deleted', 'notification' ), parent::get_taxonomy_singular_name( $taxonomy ), $taxonomy ) );

	}

	/**
	 * Assigns action callback args to object
	 *
	 * @param integer $term_id Term ID.
	 * @return mixed void or false if no notifications should be sent
	 */
	public function action( $term_id ) {

		$term = get_term( $term_id );
		$this->term = $term;

		if ( $this->taxonomy != $this->term->taxonomy ) {
			return false;
		}

		$this->taxonomy       = $this->term->taxonomy;
		$this->term_permalink = get_term_link( $this->term );

		$this->term_deletion_datetime = time();

	}

	/**
	 * Registers attached merge tags
	 *
	 * @return void
	 */
	public function merge_tags() {

		parent::merge_tags();

		$this->add_merge_tag( new MergeTag\DateTime\DateTime( array(
			'slug' => 'term_deletion_datetime',
			'name' => sprintf( __( 'Term deletion date and time', 'notification' ) ),
		) ) );

    }

}
