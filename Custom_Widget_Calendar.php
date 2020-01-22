<?php
class Custom_Widget_Calendar extends WP_Widget {

    public $category_id;
    
	function __construct() {
		
		$widget_ops = array('classname' => 'custom_widget_calendar', 'description' => __( 'A custom calendar dor espefics categories','tfuse') );
		
        parent::__construct('cusom_calendar', __('Custom Calendar','tfuse'), $widget_ops);
	}

	function widget( $args, $instance, $category_id = 0 ) {
		extract($args);
		

        $title = apply_filters('widget_title', empty($instance['title']) ? __('','tfuse') : $instance['title'], $instance, $this->id_base);
        
        $link = apply_filters('widget_link', empty($instance['link']) ? __('','tfuse') : $instance['link'], $instance, $this->id_base);
        
        $category = apply_filters('widget_category', empty($instance['category']) ? __('','tfuse') : $instance['category'], $instance, $this->id_base);
        
        $tempate = get_template_directory_uri();
                
        $title = tfuse_qtranslate($title);
                
		$before_widget = ' <div class="widget_container widget_calendar">
                                <div class="widget_ico">
                                    <img src="'.$tempate.'/images/icons/calendar_ico.png" alt="">
				</div>';
		$after_widget = '</div>';
		
		echo $before_widget;

		echo '<div id="calendar_wrap">';
                    $this->get_calendar(true, true, $category);
                    echo '<a href="'.$link.'" class="events">'.$title.'</a>';
		echo '</div>';
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
        $instance['title'] = $new_instance['title'];
        $instance['link'] = $new_instance['link'];
        $instance['category'] = $new_instance['category'];

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array('title' => '', 'link' => '', 'category' => '') );
	
        $title = $instance['title'];
        $link = $instance['link'];
        $category = $instance['category'];
        
    ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Link Title:','tfuse'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        
        <p><label for="<?php echo $this->get_field_id('link'); ?>">
            <?php _e('Cutom link:','tfuse'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('link'); ?>" name="<?php echo $this->get_field_name('link'); ?>" type="text" value="<?php echo esc_attr($link); ?>" />
        </p>
        
        <p><label for="<?php echo $this->get_field_id('category'); ?>">
            <?php _e('ID Category:','tfuse'); ?></label> <input class="widefat" id="<?php echo $this->get_field_id('category'); ?>" name="<?php echo $this->get_field_name('category'); ?>" type="text" value="<?php echo esc_attr($category); ?>" />
        </p>

    <?php
	}
	
	function get_calendar( $initial = true, $echo = true, $category_id ) {
 
	global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

	$key   = md5( $m . $monthnum . $year );
	$cache = wp_cache_get( 'get_calendar', 'calendar' );

	if ( $cache && is_array( $cache ) && isset( $cache[ $key ] ) ) {
		/** This filter is documented in wp-includes/general-template.php */
		$output = apply_filters( 'get_calendar', $cache[ $key ] );

		if ( $echo ) {
			echo $output;
			return;
		}

		return $output;
	}

	if ( ! is_array( $cache ) ) {
		$cache = array();
	}

	// Quick check. If we have no posts at all, abort!
	if ( ! $posts ) {
		$gotsome = $wpdb->get_var( "SELECT 1 as test FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' LIMIT 1" );
		if ( ! $gotsome ) {
			$cache[ $key ] = '';
			wp_cache_set( 'get_calendar', $cache, 'calendar' );
			return;
		}
	}

	if ( isset( $_GET['w'] ) ) {
		$w = (int) $_GET['w'];
	}
	// week_begins = 0 stands for Sunday
	$week_begins = (int) get_option( 'start_of_week' );

	// Let's figure out when we are
	if ( ! empty( $monthnum ) && ! empty( $year ) ) {
		$thismonth = zeroise( intval( $monthnum ), 2 );
		$thisyear  = (int) $year;
	} elseif ( ! empty( $w ) ) {
		// We need to get the month from MySQL
		$thisyear = (int) substr( $m, 0, 4 );
		//it seems MySQL's weeks disagree with PHP's
		$d         = ( ( $w - 1 ) * 7 ) + 6;
		$thismonth = $wpdb->get_var( "SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')" );
	} elseif ( ! empty( $m ) ) {
		$thisyear = (int) substr( $m, 0, 4 );
		if ( strlen( $m ) < 6 ) {
			$thismonth = '01';
		} else {
			$thismonth = zeroise( (int) substr( $m, 4, 2 ), 2 );
		}
	} else {
		$thisyear  = current_time( 'Y' );
		$thismonth = current_time( 'm' );
	}

	$unixmonth = mktime( 0, 0, 0, $thismonth, 1, $thisyear );
	$last_day  = gmdate( 't', $unixmonth );

	// Get the next and previous month and year with at least one post
	$previous = $wpdb->get_row(
		"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year 
		FROM wp_posts
        LEFT JOIN wp_term_relationships ON
            (wp_posts.ID = wp_term_relationships.object_id)
        LEFT JOIN wp_term_taxonomy ON
            (wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id)
        WHERE post_date < '$thisyear-$thismonth-01' 
            AND wp_posts.post_type = 'post'
            AND wp_posts.post_status = 'publish'
            AND wp_term_taxonomy.taxonomy = 'category'
            AND wp_term_taxonomy.term_id = $category_id
        ORDER BY post_date DESC
        LIMIT 1"
	);
	$next     = $wpdb->get_row(
		"SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
        FROM wp_posts
        LEFT JOIN wp_term_relationships ON
        	(wp_posts.ID = wp_term_relationships.object_id)
        LEFT JOIN wp_term_taxonomy ON
        	(wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id)
        WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59' 
        	AND wp_posts.post_type = 'post'
        	AND wp_posts.post_status = 'publish'
        	AND wp_term_taxonomy.taxonomy = 'category'
        	AND wp_term_taxonomy.term_id = $category_id
        ORDER BY post_date ASC
        LIMIT 1"
	);

	/* translators: Calendar caption: 1: Month name, 2: 4-digit year. */
	$calendar_caption = _x( '%1$s %2$s', 'calendar caption' );
	$calendar_output  = '<table id="wp-calendar">
	<caption>' . sprintf(
		$calendar_caption,
		$wp_locale->get_month( $thismonth ),
		gmdate( 'Y', $unixmonth )
	) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount = 0; $wdcount <= 6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday( ( $wdcount + $week_begins ) % 7 );
	}

	foreach ( $myweek as $wd ) {
		$day_name         = $initial ? $wp_locale->get_weekday_initial( $wd ) : $wp_locale->get_weekday_abbrev( $wd );
		$wd               = esc_attr( $wd );
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';

	if ( $previous ) {
		$calendar_output .= "\n\t\t" . '<td colspan="3" id="prev"><a href="' . get_month_link( $previous->year, $previous->month ) . '">&laquo; ' .
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $previous->month ) ) .
		'</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" id="prev" class="pad">&nbsp;</td>';
	}

	$calendar_output .= "\n\t\t" . '<td class="pad">&nbsp;</td>';

	if ( $next ) {
		$calendar_output .= "\n\t\t" . '<td colspan="3" id="next"><a href="' . get_month_link( $next->year, $next->month ) . '">' .
			$wp_locale->get_month_abbrev( $wp_locale->get_month( $next->month ) ) .
		' &raquo;</a></td>';
	} else {
		$calendar_output .= "\n\t\t" . '<td colspan="3" id="next" class="pad">&nbsp;</td>';
	}

	$calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';

	$daywithpost = array();

	// Get days with posts
	$dayswithposts = $wpdb->get_results(
		"SELECT DISTINCT DAYOFMONTH(post_date), wp_posts.id
        FROM wp_posts
        LEFT JOIN wp_term_relationships ON
        	(wp_posts.ID = wp_term_relationships.object_id)
        LEFT JOIN wp_term_taxonomy ON
        	(wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id)
        WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
        	AND wp_posts.post_type = 'post'
        	AND wp_posts.post_status = 'publish'
        	AND wp_term_taxonomy.taxonomy = 'category'
        	AND wp_term_taxonomy.term_id = $category_id
        	AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'",
		ARRAY_N
	);

	if ( $dayswithposts ) {
		foreach ( (array) $dayswithposts as $daywith ) {
			$daywithpost[] = $daywith[0];
			$idposts[$daywith[0]] = $daywith[1];
		}
	}

	// See how much we should pad in the beginning
	$pad = calendar_week_mod( gmdate( 'w', $unixmonth ) - $week_begins );
	if ( 0 != $pad ) {
		$calendar_output .= "\n\t\t" . '<td colspan="' . esc_attr( $pad ) . '" class="pad">&nbsp;</td>';
	}

	$newrow      = false;
	$daysinmonth = (int) gmdate( 't', $unixmonth );

	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset( $newrow ) && $newrow ) {
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		}
		$newrow = false;

		if ( $day == current_time( 'j' ) &&
			$thismonth == current_time( 'm' ) &&
			$thisyear == current_time( 'Y' ) ) {
			$calendar_output .= '<td id="today">';
		} else {
			$calendar_output .= '<td>';
		}

		if ( in_array( $day, $daywithpost ) ) {
			// any posts today?
			$date_format = gmdate( _x( 'F j, Y', 'daily archives date format' ), strtotime( "{$thisyear}-{$thismonth}-{$day}" ) );
			/* translators: Post calendar label. %s: Date. */
			$label            = sprintf( __( 'Posts published on %s' ), $date_format );
			$calendar_output .= sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				//get_day_link( $thisyear, $thismonth, $day ),
				get_permalink($idposts[$day]),
				esc_attr( $label ),
				$day
			);
		} else {
			$calendar_output .= $day;
		}
		$calendar_output .= '</td>';

		if ( 6 == calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins ) ) {
			$newrow = true;
		}
	}

	$pad = 7 - calendar_week_mod( gmdate( 'w', mktime( 0, 0, 0, $thismonth, $day, $thisyear ) ) - $week_begins );
	if ( $pad != 0 && $pad != 7 ) {
		$calendar_output .= "\n\t\t" . '<td class="pad" colspan="' . esc_attr( $pad ) . '">&nbsp;</td>';
	}
	$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";

	$cache[ $key ] = $calendar_output;
	wp_cache_set( 'get_calendar', $cache, 'calendar' );

	if ( $echo ) {
		/**
		 * Filters the HTML calendar output.
		 *
		 * @since 3.0.0
		 *
		 * @param string $calendar_output HTML output of the calendar.
		 */
		echo apply_filters( 'get_calendar', $calendar_output );
		return;
	}
	/** This filter is documented in wp-includes/general-template.php */
	return apply_filters( 'get_calendar', $calendar_output );
}
}


//function Custom_Unregister_WP_Widget_Calendar() {
//	unregister_widget('WP_Widget_Calendar');
//}

//add_action('widgets_init','Custom_Unregister_WP_Widget_Calendar');

//register_widget('Custom_Widget_Calendar');

function cwc_load_widget() {
    register_widget( 'Custom_Widget_Calendar' );
}
add_action( 'widgets_init', 'cwc_load_widget' );
