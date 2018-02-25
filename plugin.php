<?php
/*
  Plugin Name: Top Stories Widget
  Plugin URI: http://codecanyon.net/item/top-stories-most-viewed-commented-posts-widget/4076959
  Description: Add your most commented (or viewed) posts in your sidebar with unlimited color bars, Engadget-like style.
  Version: v3.1
  Author: Daniel Gutierrez
  Author URI: http://dagtok.com/
*/

global $wpdb,$popular_post_table, $google_fonts;

include 'includes/google_fonts.php';


$popular_post_table = $wpdb->prefix.'plugin_kbl_tsww_most_popular_posts';

class DGT_TopStories extends WP_Widget {
  function DGT_TopStories() { /** constructor */
      parent::WP_Widget(false, $name = 'Top Stories Widget');	
  }

  function top_stories_activate(){
    global $wpdb, $popular_post_table;
    $new_posts = null;

    $wpdb->query("CREATE TABLE `" . $popular_post_table . "` (
        `id` bigint(50) unsigned NOT NULL AUTO_INCREMENT,
        `post_id` int(11) unsigned NOT NULL,
        `date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`,`post_id`),
        UNIQUE KEY `id` (`id`),
        KEY `post_id` (`post_id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;");

    $recent_posts = wp_get_recent_posts(
          array(
            'numberposts' => 20,
            'offset' => 0,
            'category' => NULL,
            'orderby' => 'id',
            'order' => 'DESC',
            'include' => NULL,
            'exclude' => NULL,
            'meta_key' => NULL,
            'meta_value' => NULL,
            'post_type' => 'post',
            'post_status' => 'publish',
            'suppress_filters' => true )
          );

    
    $no_posts = sizeof($recent_posts);
    
    for ($i=1; $i <= $no_posts; $i++) {
      $new_posts .= "('$i', '" .$recent_posts[$i-1]['ID'] . "', '" .date("c", current_time('timestamp', 0)). "')".($i == $no_posts ? "; " : ", ");
    }

    $temp_most_populars = "INSERT INTO `" . $popular_post_table . "` VALUES ". $new_posts;
    $wpdb->query($temp_most_populars);
  }

  function top_stories_deactivate(){
    global $wpdb, $popular_post_table;
    $wpdb->query("DROP TABLE " . $popular_post_table );
    
    remove_action('wp_enqueue_scripts', array('DGT_TopStories', 'kbl_top_stories_scripts_loaded'));
    remove_action('wp_head', array('DGT_TopStories', 'register_view'));
        
    unregister_widget('DGT_TopStories');
  }

  /** @see WP_Widget::form */
  function form($instance) {
    global $google_fonts;

    $default = array(
        'title' => 'Top Stories on our blog',
        'title_color' => '#000000',
        'title_font_family' => 'Open Sans Condensed',
        'title_font_size' => '10',
        'title_font_color' => '#ffffff',
        'font_color' => '#ffffff',
        'background_color' => '#000000',
        'background_color2' => '#000000',
        'fixed_background' => FALSE,
        'force_header_style' => FALSE,
        'animation' => FALSE,
        'date_color' => '#ff7f00',
        'font_date_color' => '#ffffff',
        'post_number' => 10,
        'bg_gradient' => FALSE,
        'effect' => 'default',
        'height' => 0,
        'width' => 0,
        'displaydate' => 0,
        'displaycategory' => 0,
        'showcounter' => 0,
        'content_type' => 'populars',
        'widget_body_font_size' => 23,
        'widget_body_font_family' => 'Open Sans Condensed',
        'included_categories' => '*',
        'period_interval' => 'yearly',
        'category_filter' => null,
        'tag_filter' => null,
        'exclude_category_filter' => null,
        'stats_period_interval' => '+5 Minutes',
        'limit_stats_date' => array(),
        'limit_content_date' => array(),
        'data' => array()
    );
    
    $instance = wp_parse_args( (array) $instance, $default );

    $instance['content_type'] = (isset($instance['content_type']) ? $instance['content_type'] : 'Pages');
    $title = isset($instance['title']) ? esc_attr($instance['title']) : NULL;

    $custom_post_types = $this->get_custom_posts_list();
    $blog_categories = get_categories( array('orderby' => 'name', 'order' => 'ASC') );
    $blog_tags = get_tags();
    $blog_tag_no = sizeof($blog_tags);

    $tmp_google_fonts = json_decode($google_fonts);
    $tmp_google_fonts = $tmp_google_fonts->items;
    $google_fonts_no = sizeof($tmp_google_fonts);

    ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo __('Title:','top_stories'); ?> 
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('content_type'); ?>"><?php echo __('Select the type of content displayed:','top_stories'); ?>
      <select name="<?php echo $this->get_field_name('content_type'); ?>" id="<?php echo $this->get_field_id('content_type'); ?>" class="widefat" onChange="showFieldBoxes(this)">
        <option value="pages" <?php echo ($instance['content_type'] == 'pages' ? 'selected' : NULL)  ?>><?php echo __('Pages','top_stories'); ?></option>
        <option value="recents" <?php echo ($instance['content_type'] == 'recents' ? 'selected' : NULL)  ?>><?php echo __('Recent Posts','top_stories'); ?></option>
        <option value="custom_post_type" <?php echo ($instance['content_type'] == 'custom_post_type' ? 'selected' : NULL)  ?>><?php echo __('Custom Post Type','top_stories'); ?></option>
        <option value="populars" <?php echo ($instance['content_type'] == 'populars' ? 'selected' : NULL)  ?>><?php echo __('Popular Posts','top_stories'); ?></option>
        <option value="posts_by_tag" <?php echo ($instance['content_type'] == 'posts_by_tag' ? 'selected' : NULL)  ?>><?php echo __('Posts by tag','top_stories'); ?></option>
        <option value="commented" <?php echo ($instance['content_type'] == 'commented' ? 'selected' : NULL)  ?>><?php echo __('Most Commented','top_stories'); ?></option>
      </select>
    </p>

    <div id="<?php echo $this->get_field_id('display_custom_post_type'); ?>" style="display:none">
      <p>
        <label for="<?php echo $this->get_field_id('label_custom_post_type'); ?>"><?php echo esc_attr(__('Select the post types you want to display:','top_stories')); ?></label>
        <ul class="post-types" <?php echo ((isset($instance['width']) AND $instance['width'] > 0) ? 'style="width:'.$instance['width'].'px;"' : NULL); ?>>
          <?php
          if(is_array($custom_post_types)){
            foreach ($custom_post_types as $post_type) {
              $option='<li><input type="checkbox" id="'. $this->get_field_id( 'custom_post_types' ) .'[]" name="'. $this->get_field_name( 'custom_post_types' ) .'[]"';
              
              if(isset($instance['custom_post_types']) AND is_array($instance['custom_post_types'])) {
                foreach ($instance['custom_post_types'] as $post_types) {
                  if($post_types == $post_type['post_type_name']) {
                    $option=$option.' checked="checked"';
                  }
                }
              }

              $option .= ' value="'.$post_type['post_type_name'].'" />';
              $option .= $post_type['display_name'];
              $option .= '<br />';
              
              echo $option;
            }
          } else {
            echo "<li style='background-color:red; color:white'>There are no registered custom post type</li>";
          }
          ?>
        </ul>
      </p>
    </div>

    <div id="<?php echo $this->get_field_id('display_date_filter'); ?>" style="display:none">
      
      <p>
        <label for="<?php echo $this->get_field_id('period_interval'); ?>">
          <?php echo __('Select the period range you want to be filtering by','top_stories'); ?>
        </label>
        <select 
          name="<?php echo $this->get_field_name('period_interval'); ?>" 
          id="<?php echo $this->get_field_id('period_interval'); ?>" 
          class="widefat">
          <option value="daily" <?php echo ($instance['period_interval'] == 'daily' ? 'selected' : NULL)  ?>><?php echo __('Most populars for today','top_stories'); ?></option>
          <option value="weekly" <?php echo ($instance['period_interval'] == 'weekly' ? 'selected' : NULL)  ?>><?php echo __('Most populars this week','top_stories'); ?></option>
          <option value="monthly" <?php echo ($instance['period_interval'] == 'monthly' ? 'selected' : NULL)  ?>><?php echo __('Most populars this month','top_stories'); ?></option>
          <option value="yearly" <?php echo ($instance['period_interval'] == 'yearly' ? 'selected' : NULL)  ?>><?php echo __('Most populars this year','top_stories'); ?></option>
        </select>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('stats_period_interval'); ?>"><?php echo __('Refresh widget stats and content every:','top_stories'); ?>
        <select name="<?php echo $this->get_field_name('stats_period_interval'); ?>" id="<?php echo $this->get_field_id('stats_period_interval'); ?>" class="widefat">
          <option value="+2 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+2 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 2 minutes','top_stories'); ?>
          </option>
          <option value="+5 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+5 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 5 minutes','top_stories'); ?>
          </option>
          <option value="+10 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+10 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 10 minutes','top_stories'); ?>
          </option>
          <option value="+20 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+20 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 20 minutes','top_stories'); ?>
          </option>
          <option value="+30 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+30 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 30 minutes','top_stories'); ?>
          </option>
          <option value="+40 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+40 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 40 minutes','top_stories'); ?>
          </option>
          <option value="+50 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+50 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 50 minutes','top_stories'); ?>
          </option>
          <option value="+60 Minutes"
          <?php echo ($instance['stats_period_interval'] == '+60 Minutes' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every hour','top_stories'); ?>
          </option>
          <option value="+2 Hours"
          <?php echo ($instance['stats_period_interval'] == '+2 Hours' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 2 hours','top_stories'); ?>
          </option>
          <option value="+4 Hours"
          <?php echo ($instance['stats_period_interval'] == '+4 Hours' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 4 hours','top_stories'); ?>
          </option>
          <option value="+6 Hours"
          <?php echo ($instance['stats_period_interval'] == '+6 Hours' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 6 hours','top_stories'); ?>
          </option>
          <option value="+8 Hours"
          <?php echo ($instance['stats_period_interval'] == '+8 Hours' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 8 hours','top_stories'); ?>
          </option>
          <option value="+12 Hours"
          <?php echo ($instance['stats_period_interval'] == '+12 Hours' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every 12 hours','top_stories'); ?>
          </option>
          <option value="+1 day"
          <?php echo ($instance['stats_period_interval'] == '+1 day"' ? 'selected' : NULL)  ?>>
            <?php echo __('Update every day','top_stories'); ?>
          </option>
        </select>
      </p>

    </div>
  
    <div id="<?php echo $this->get_field_id('display_tag_filter'); ?>">
        <p>
          <label for="<?php echo $this->get_field_id('tag_filter'); ?>"><?php echo esc_attr(__('Select tags that will be included in this widget:','top_stories')); ?></label>
          <ul class="popular-publications" <?php echo ((isset($instance['width']) AND $instance['width'] > 0) ? 'style="width:'.$instance['width'].'px;"' : NULL); ?>>
            <?php
            //     echo $blog_tags[$i]->slug.'<br><br>';
            for ($i=0; $i < $blog_tag_no; $i++) { 
              $option='<li><input type="checkbox" id="'. $this->get_field_id( 'tag_filter' ) .'[]" name="'. $this->get_field_name( 'tag_filter' ) .'[]"';
              if(isset($instance['tag_filter']) AND is_array($instance['tag_filter'])) {
                foreach ($instance['tag_filter'] as $cats) {
                  if($cats == $blog_tags[$i]->term_id) {
                    $option=$option.' checked="checked"';
                  }
                }
              }
              $option .= ' value="'.$blog_tags[$i]->term_id.'" data-wg-id="'. $this->get_field_id( 'tag_filter' ) .'" data-type="include" onclick="tag_filter_control(this)" />';
              $option .= $blog_tags[$i]->name;
              $option .= '</li>';
              echo $option;
            }
            ?>
          </ul>
        </p>
    </div>
    
    <div id="<?php echo $this->get_field_id('display_advance_filters_div'); ?>" style="display:none">    
      <p>
        <label for="<?php echo $this->get_field_id('display_advance_filters'); ?>"><?php echo esc_attr(__('Show advance filters:','top_stories')); ?></label>
        <?php
        echo '<input type="checkbox" 
              id="'. $this->get_field_id( 'display_advance_filters' ) .'" 
              name="'. $this->get_field_name( 'display_advance_filters' ) .'" 
              value="true"
              onclick="toggleAdvanceFilterPanel(this)"
              '.( (isset($instance['display_advance_filters']) && $instance['display_advance_filters'] == 'true') ? 'checked="checked"' : null).'
              />';
        ?>
      </p>
    </div>

    <div id="<?php echo $this->get_field_id('display_category_filter'); ?>" style="display:none">
        <p>
          <label for="<?php echo $this->get_field_id('category_filter'); ?>"><?php echo esc_attr(__('Select categories to be included in this widget:','top_stories')); ?></label>
          <ul class="popular-publications" <?php echo ((isset($instance['width']) AND $instance['width'] > 0) ? 'style="width:'.$instance['width'].'px;"' : NULL); ?>>
            <?php
            for ($i=0; $i < sizeof($blog_categories); $i++) {
              $option='<li><input type="checkbox" id="'. $this->get_field_id( 'category_filter' ) .'[]" name="'. $this->get_field_name( 'category_filter' ) .'[]"';
              if(isset($instance['category_filter']) AND is_array($instance['category_filter'])) {
                foreach ($instance['category_filter'] as $cats) {
                  if($cats == $blog_categories[$i]->cat_ID) {
                    $option=$option.' checked="checked"';
                  }
                }
              }
              $option .= ' value="'.$blog_categories[$i]->cat_ID.'" data-wg-id="'. $this->get_field_id( 'category_filter' ) .'" data-type="include" onclick="category_filter_control(this)" />';
              $option .= $blog_categories[$i]->name;
              $option .= '</li>';
              echo $option;
            }
            ?>
          </ul>
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('exclude_category_filter'); ?>"><?php echo esc_attr(__('Select categories to be excluded from this widget:','top_stories')); ?></label>
          <ul class="popular-publications" <?php echo ((isset($instance['width']) AND $instance['width'] > 0) ? 'style="width:'.$instance['width'].'px;"' : NULL); ?>>
            <?php
            for ($j=0; $j < sizeof($blog_categories); $j++) {
              $option='<li><input type="checkbox" id="'. $this->get_field_id( 'exclude_category_filter' ) .'[]" name="'. $this->get_field_name( 'exclude_category_filter' ) .'[]"';
              if(isset($instance['exclude_category_filter']) AND is_array($instance['exclude_category_filter'])) {
                foreach ($instance['exclude_category_filter'] as $cats) {
                  if($cats == $blog_categories[$j]->cat_ID) {
                    $option=$option.' checked="checked"';
                  }
                }
              }
              $option .= ' value="'.$blog_categories[$j]->cat_ID.'" data-wg-id="'. $this->get_field_id( 'category_filter' ) .'" data-type="exclude" onclick="category_filter_control(this)" />';
              $option .= $blog_categories[$j]->name;
              $option .= '</li>';
              echo $option;
            }
            ?>
          </ul>
        </p>
    </div>

    <div id="<?php echo $this->get_field_id('display_check_pages'); ?>" style="display:none">
      <p>
        <label for="<?php echo $this->get_field_id('background_color'); ?>"><?php echo esc_attr(__('Select pages will be shown on this widget:','top_stories')); ?></label>
        
        <ul class="popular-publications" <?php echo ((isset($instance['width']) AND $instance['width'] > 0) ? 'style="width:'.$instance['width'].'px;"' : NULL); ?>>
          <?php
          $pages = get_pages(); 
          foreach ($pages as $page) {
            $option='<li><input type="checkbox" id="'. $this->get_field_id( 'showed_pages' ) .'[]" name="'. $this->get_field_name( 'showed_pages' ) .'[]"';
            if( isset($instance['showed_pages']) AND is_array($instance['showed_pages']) ) {
              
              foreach ($instance['showed_pages'] as $cats) {
                if($cats==$page->ID) {
                  $option=$option.' checked="checked"';
                }
              }

            }

            $option .= ' value="'.$page->ID.'" />';
                  $option .= $page->post_title;
                  $option .= '<br />';
                  echo $option;
              }
          ?>
        </ul>
      </p>
    </div>

    <p>
      <label for="<?php echo $this->get_field_id('post_number'); ?>"><?php echo __('Indicate the maximum number of posts to display:','top_stories'); ?>
        <input name="<?php echo $this->get_field_name('post_number'); ?>" id="<?php echo $this->get_field_id('post_number'); ?>" type="number" min="1" max="30" class="widefat" value="<?php echo esc_attr($instance['post_number']); ?>" />
      </label>
    </p>

    <p>
      <input id="<?php echo $this->get_field_id('fixed_background'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('fixed_background'); ?>" 
      value="TRUE" <?php echo ($instance['fixed_background'] == TRUE) ? ' checked="checked"' : NULL; ?> >
      <?php echo esc_attr(__('Force background image to feed widget container','kbl_txt')); ?><br>

      <input id="<?php echo $this->get_field_id('animation'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('animation'); ?>" 
      value="TRUE" 
      onclick="var selector = '#'+jQuery(this).attr('id').replace('animation','display_over_effects'); if(jQuery(this).attr('checked') == 'checked'){ jQuery(selector).fadeIn('slow'); } else { jQuery(selector).fadeOut('slow'); }" 
      <?php echo ($instance['animation'] == TRUE) ? ' checked="checked"' : NULL; ?>
      >
      <?php echo esc_attr(__('Show content animation on mouse over','kbl_txt')); ?><br>
    </p>

    <div id="<?php echo $this->get_field_id('display_over_effects'); ?>" style="display:none">
      <p>
        <label for="<?php echo $this->get_field_id('effect'); ?>"><?php echo __('Select mouse over animation effect:','top_stories'); ?>
        <select name="<?php echo $this->get_field_name('effect'); ?>" id="<?php echo $this->get_field_id('effect'); ?>" class="widefat">
          <option value="default" <?php echo ($instance['effect'] == 'default' ? 'selected' : NULL) ?>>DEFAULT</option>
          <option value="maria" <?php echo ($instance['effect'] == 'maria' ? 'selected' : NULL) ?>>MARIA</option>
          <option value="rosa" <?php echo ($instance['effect'] == 'rosa' ? 'selected' : NULL) ?>>ROSA</option>
          <option value="juana" <?php echo ($instance['effect'] == 'juana' ? 'selected' : NULL) ?>>JUANA</option>
          <option value="juana2" <?php echo ($instance['effect'] == 'juana2' ? 'selected' : NULL) ?>>JUANA STYLE 2</option>
          <option value="agustina" <?php echo ($instance['effect'] == 'agustina' ? 'selected' : NULL) ?>>AGUSTINA</option>
          <option value="agustina2" <?php echo ($instance['effect'] == 'agustina2' ? 'selected' : NULL) ?>>AGUSTINA STYLE STYLE 2</option>
          <option value="amapola" <?php echo ($instance['effect'] == 'amapola' ? 'selected' : NULL) ?>>AMAPOLA</option>
          <option value="julia" <?php echo ($instance['effect'] == 'julia' ? 'selected' : NULL) ?>>JULIA</option>
          <option value="julia2" <?php echo ($instance['effect'] == 'julia2' ? 'selected' : NULL) ?>>JULIA STYLE 2</option>
          <option value="catalina" <?php echo ($instance['effect'] == 'catalina' ? 'selected' : NULL) ?>>CATALINA</option>
          <option value="catalina2" <?php echo ($instance['effect'] == 'catalina2' ? 'selected' : NULL) ?>>CATALINA STYLE 2</option>
          <option value="aurelia" <?php echo ($instance['effect'] == 'aurelia' ? 'selected' : NULL) ?>>AURELIA</option>
          <option value="aurelia2" <?php echo ($instance['effect'] == 'aurelia2' ? 'selected' : NULL) ?>>AURELIA STYLE 2</option>
          <option value="sabina" <?php echo ($instance['effect'] == 'sabina' ? 'selected' : NULL) ?>>SABINA</option>
          <option value="sabina2" <?php echo ($instance['effect'] == 'sabina2' ? 'selected' : NULL) ?>>SABINA STYLE 2</option>
          <option value="trinidad" <?php echo ($instance['effect'] == 'trinidad' ? 'selected' : NULL) ?>>TRINIDAD</option>
          <option value="felicitas" <?php echo ($instance['effect'] == 'felicitas' ? 'selected' : NULL) ?>>FELICITAS</option>
          <option value="felicitas2" <?php echo ($instance['effect'] == 'felicitas2' ? 'selected' : NULL) ?>>FELICITAS STYLE 2</option>
          <option value="mercedes" <?php echo ($instance['effect'] == 'mercedes' ? 'selected' : NULL) ?>>MERCEDES</option>
          <option value="mercedes2" <?php echo ($instance['effect'] == 'mercedes2' ? 'selected' : NULL) ?>>MERCEDES STYLE 2</option>
          <option value="anastacia" <?php echo ($instance['effect'] == 'anastacia' ? 'selected' : NULL) ?>>ANASTACIA</option>
          <option value="casilda" <?php echo ($instance['effect'] == 'casilda' ? 'selected' : NULL) ?>>CASILDA</option>
          <option value="casilda2" <?php echo ($instance['effect'] == 'casilda2' ? 'selected' : NULL) ?>>CASILDA STYLE 2</option>
          <option value="catarina" <?php echo ($instance['effect'] == 'catarina' ? 'selected' : NULL) ?>>CATARINA</option>
          <option value="catarina2" <?php echo ($instance['effect'] == 'catarina2' ? 'selected' : NULL) ?>>CATARINA STYLE 2</option>
          <option value="fatima" <?php echo ($instance['effect'] == 'fatima' ? 'selected' : NULL) ?>>FATIMA</option>
          <option value="fatima2" <?php echo ($instance['effect'] == 'fatima2' ? 'selected' : NULL) ?>>FATIMA STYLE 2</option>
          <option value="marcelina" <?php echo ($instance['effect'] == 'marcelina' ? 'selected' : NULL) ?>>MARCELINA</option>
          <option value="marcelina2" <?php echo ($instance['effect'] == 'marcelina2' ? 'selected' : NULL) ?>>MARCELINA STYLE 2</option>
          <option value="emeteria" <?php echo ($instance['effect'] == 'emeteria' ? 'selected' : NULL) ?>>EMETERIA</option>
        </select>
      </p>
    </div>

    <p id="<?php echo $this->get_field_id('date_tag_style'); ?>" style="display:none">
      
      <input id="<?php echo $this->get_field_id('displaydate'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('displaydate'); ?>" 
      value="1" 
      onclick="var selector = '#'+jQuery(this).attr('id').replace('displaydate',''); if(jQuery(this).attr('checked') == 'checked'){ jQuery(selector+'date_tag').fadeIn('slow'); } else { jQuery(selector+'date_tag').fadeOut('slow'); } if(jQuery(selector + 'displaydate').prop('checked') == true){jQuery(selector + 'displaycategory').prop('checked', false);}"
      <?php echo ($instance['displaydate'] == 1) ? ' checked="checked"' : NULL; ?> >
      <?php echo esc_attr(__('Display date container','kbl_txt')); ?><br>

      <input id="<?php echo $this->get_field_id('displaycategory'); ?>"
      type="checkbox"
      name="<?php echo $this->get_field_name('displaycategory'); ?>"
      onclick="var selector = '#'+jQuery(this).attr('id').replace('displaycategory',''); if(jQuery(this).attr('checked') == 'checked'){ jQuery(selector+'date_tag').fadeIn('slow'); } else { jQuery(selector+'date_tag').fadeOut('slow'); } if(jQuery(selector + 'displaycategory').prop('checked') == true){jQuery(selector + 'displaydate').prop('checked', false);}"
      value="1" <?php echo ($instance['displaycategory'] == 1) ? ' checked="checked"' : NULL; ?>>
      <?php echo esc_attr(__('Display category container','kbl_txt')); ?><br>

      <input id="<?php echo $this->get_field_id('showcounter'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('showcounter'); ?>" 
      value="1" 
      <?php echo ($instance['showcounter'] == 1) ? ' checked="checked"' : NULL; ?>> <?php echo esc_attr(__('Display visits counter','kbl_txt')); ?><br>


      <input id="<?php echo $this->get_field_id('bg_gradient'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('bg_gradient'); ?>" 
      value="1" 
      onclick="var selector = '#'+jQuery(this).attr('id').replace('bg_gradient','gradient_color_picker'); if(jQuery(this).attr('checked') == 'checked'){ jQuery(
        selector).fadeIn('slow'); } else { jQuery(selector).fadeOut('slow'); }" <?php echo ($instance['bg_gradient'] == 1) ? ' checked="checked"' : NULL; ?>> <?php echo esc_attr(__('Display Background Gradient','kbl_txt')); ?><br>

      <input id="<?php echo $this->get_field_id('force_header_style'); ?>" 
      type="checkbox" 
      name="<?php echo $this->get_field_name('force_header_style'); ?>" 
      onclick="var selector = '#'+jQuery(this).attr('id').replace('force_header_style','header_style'); if(jQuery(this).attr('checked') == 'checked'){ jQuery(selector).fadeOut('slow'); } else { jQuery(selector).fadeIn('slow'); }" 
      value="TRUE" <?php echo ($instance['force_header_style'] == TRUE) ? ' checked="checked"' : NULL; ?> >
      <?php echo esc_attr(__('Keep widget\'s header theme style','kbl_txt')); ?><br>

    </p>
    
    <div id="<?php echo $this->get_field_id('header_style'); ?>" style="display:none">
      <hr>
      <h4>Widget Style</h4>
      <p>
        <label for="<?php echo $this->get_field_id('width'); ?>"><?php echo __('Indicate the width for widget sidebar:','top_stories'); ?>
          <input name="<?php echo $this->get_field_name('width'); ?>" id="<?php echo $this->get_field_id('width'); ?>" type="number" min="1" max="30" class="widefat" value="<?php echo esc_attr($instance['width']); ?>" />
        </label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('height'); ?>"><?php echo __('Indicate the height for widget sidebar:','top_stories'); ?>
          <input name="<?php echo $this->get_field_name('height'); ?>" id="<?php echo $this->get_field_id('height'); ?>" type="number" min="1" max="30" class="widefat" value="<?php echo esc_attr($instance['height']); ?>" />
        </label>
      </p>
      <hr>
      <h4>Title Style</h4>
      <p>
        <label for="<?php echo $this->get_field_id('title_font_family'); ?>"><?php echo __('Select font family for widget title:','top_stories'); ?>
          <select name="<?php echo $this->get_field_name('title_font_family'); ?>" id="<?php echo $this->get_field_id('title_font_family'); ?>" class="widefat">
            <?php
            for ($i=0; $i < $google_fonts_no; $i++) { 
              echo '<option value="'.urlencode($tmp_google_fonts[$i]->family).'" '.($instance['title_font_family'] == urlencode($tmp_google_fonts[$i]->family) ? 'selected' : NULL).'>'.$tmp_google_fonts[$i]->family.'</option>';
            }
            ?>
          </select>
        </label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('title_font_size'); ?>"><?php echo __('Select font size for widget title:','top_stories'); ?>
          <input name="<?php echo $this->get_field_name('title_font_size'); ?>" id="<?php echo $this->get_field_id('title_font_size'); ?>" type="number" min="0" max="20" class="widefat" value="<?php echo esc_attr($instance['title_font_size']); ?>" />
        </label>
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('title_font_color'); ?>"><?php echo __('Choose font color for widget title:','top_stories'); ?></label>
        <input id="<?php echo $this->get_field_id('title_font_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('title_font_color'); ?>" value="<?php echo $instance['title_font_color']; ?>" />
      </p>

      <p>
        <label for="<?php echo $this->get_field_id('title_color'); ?>"><?php echo __('Choose background color for widget title:','top_stories'); ?></label>
        <input id="<?php echo $this->get_field_id('title_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('title_color'); ?>" value="<?php echo $instance['title_color']; ?>" />
      </p>
    </div>

    <hr>

    <h4>Widget Body Style</h4>

    <label for="<?php echo $this->get_field_id('widget_body_font_family'); ?>"><?php echo __('Select font family for widget body:','top_stories'); ?>
      <select name="<?php echo $this->get_field_name('widget_body_font_family'); ?>" id="<?php echo $this->get_field_id('widget_body_font_family'); ?>" class="widefat">
        <?php
        for ($i=0; $i < $google_fonts_no; $i++) { 
          echo '<option value="'.urlencode($tmp_google_fonts[$i]->family).'" '.($instance['widget_body_font_family'] == urlencode($tmp_google_fonts[$i]->family) ? 'selected' : NULL).'>'.$tmp_google_fonts[$i]->family.'</option>';
        }
        ?>
      </select>
    </label>

    <p>
      <label for="<?php echo $this->get_field_id('widget_body_font_size'); ?>"><?php echo __('Select font size for widget title:','top_stories'); ?>
        <input name="<?php echo $this->get_field_name('widget_body_font_size'); ?>" id="<?php echo $this->get_field_id('widget_body_font_size'); ?>" type="number" min="0" max="20" class="widefat" value="<?php echo esc_attr($instance['widget_body_font_size']); ?>" />
      </label>
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('font_color'); ?>">
        <?php echo __('Choose Font color:','top_stories'); ?>
      </label>
      <input id="<?php echo $this->get_field_id('font_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('font_color'); ?>" value="<?php echo $instance['font_color']; ?>" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('background_color'); ?>">
        <?php echo __('Choose background color:','top_stories'); ?>
      </label>
      <input id="<?php echo $this->get_field_id('background_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('background_color'); ?>" value="<?php echo $instance['background_color']; ?>" />
    </p>

    <div id="<?php echo $this->get_field_id('gradient_color_picker'); ?>" style="display:<?php echo ($instance['bg_gradient']) ? 'block' : 'none'; ?>">
      <p>
        <label for="<?php echo $this->get_field_id('background_color2'); ?>"><?php echo __('Choose background color 2:','top_stories'); ?></label>
        <input id="<?php echo $this->get_field_id('background_color2'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('background_color2'); ?>" value="<?php echo $instance['background_color2']; ?>" />
      </p>
    </div>

    <div id="<?php echo $this->get_field_id('date_tag'); ?>" style="display:none">
      <hr>
      <h4>Date/Category Tag</h4>
      <p>
        <label for="<?php echo $this->get_field_id('date_color'); ?>"><?php _e('Choose background color for date tag:','top_stories'); ?></label>
        <input id="<?php echo $this->get_field_id('date_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('date_color'); ?>" value="<?php echo $instance['date_color']; ?>" />
      </p>
      <p>
        <label for="<?php echo $this->get_field_id('font_date_color'); ?>"><?php _e('Choose font color for date tag:','top_stories'); ?></label>
        <input id="<?php echo $this->get_field_id('font_date_color'); ?>" type="color" class="widefat" name="<?php echo $this->get_field_name('font_date_color'); ?>" value="<?php echo $instance['font_date_color']; ?>" />
      </p>
    </div>

    <script type="text/javascript">
      var d;
      jQuery(document).ready(function(){
        
        id = '#<?php echo $this->get_field_id(''); ?>';
        
        content_type = jQuery(id+'content_type').val();

        if(content_type == 'pages') {
          
          jQuery(id+'display_check_pages').show();
          jQuery(id+'display_advance_filters_div').hide();
          jQuery(id+'display_category_filter').hide();
          jQuery(id+'date_tag_style').hide(); //Hide date cause pages does not have this data
          jQuery(id+'display_custom_post_type').hide();
          jQuery(id+'display_date_filter').hide();

          if(jQuery(id+'displaydate').prop('checked') == true || jQuery(id+'displaycategory').prop('checked') == true) {
            jQuery(id+'date_tag').fadeIn();
          } else {
            jQuery(id+'date_tag').fadeOut();
          }

        } else { //Content type is Recent posts, A custom post type, Popular post, Most commented
          
          jQuery(id+'display_check_pages').hide();
          jQuery(id+'display_advance_filters_div').show();
          
          if(jQuery(id+'display_advance_filters').prop('checked') == true){
            jQuery(id+'display_category_filter').show();
          } else {
            jQuery(id+'display_category_filter').hide();
          }

          jQuery(id+'date_tag_style').show();

          if(content_type == 'populars'){
            jQuery(id+'display_date_filter').show();
          } else {
            jQuery(id+'display_date_filter').hide();
          }

          if(content_type == 'custom_post_type'){
            jQuery(id+'display_custom_post_type').show();
          } else {
            jQuery(id+'display_custom_post_type').hide();
          }

          if(content_type == 'posts_by_tag') {
            jQuery(id+'display_tag_filter').show();
          } else {
            jQuery(id+'display_tag_filter').hide();
          }

        }

        if(jQuery(id+'animation').prop('checked') == true){
          jQuery(id+'display_over_effects').show();
        }
        
        if(jQuery(id+'force_header_style').prop('checked') == true) {
          jQuery(id+'header_style').hide();
        } else {
          jQuery(id+'header_style').show();
        }
        
      });

    function toggleAdvanceFilterPanel(element){
      var id = '#' + jQuery(element).attr('id').replace('display_advance_filters', '');

      if(jQuery(id+'display_advance_filters').prop('checked') == true){
        jQuery(id+'display_category_filter').slideDown();
      } else {
        jQuery(id+'display_category_filter').slideUp();
      }

    }

    function showFieldBoxes(element){ //Switch form elements depending on type of content selected
      
      var id = '#' + jQuery(element).attr('id').replace('content_type', '');
      var content_type = jQuery(element).val();

      if(jQuery(element).val() == 'pages'){

        jQuery(id+'display_check_pages').fadeIn(); //Show pages selected on "pick up section"
        jQuery(id+'display_advance_filters_div').hide();
        jQuery(id+'display_category_filter').hide();
        jQuery(id+'date_tag_style').fadeOut();
        jQuery(id+'display_custom_post_type').hide();
        jQuery(id+'display_date_filter').hide();
        

        if(jQuery(id+'displaydate').prop('checked') == true || jQuery(id+'displaycategory').prop('checked') == true) {
          jQuery(id+'date_tag').fadeIn();
        } else {
          jQuery(id+'date_tag').fadeOut();
        }

      } else { //Content displayed on widget is Recent posts, A custom post type, Popular post, Most commented
          
        jQuery(id+'display_check_pages').hide(); //Display the section to select pages that will be shown on widget
        jQuery(id+'display_advance_filters_div').fadeIn();
        jQuery(id+'date_tag_style').fadeIn();
        
        if(content_type == 'populars') {
          jQuery(id+'display_date_filter').fadeIn();
        } else {
          jQuery(id+'display_date_filter').fadeOut();
        }

        if(content_type == 'custom_post_type') {
          jQuery(id+'display_custom_post_type').slideDown();
        } else {
          jQuery(id+'display_custom_post_type').fadeOut();
        }


        if(content_type == 'posts_by_tag') {
          jQuery(id+'display_tag_filter').fadeIn();
        } else {
          jQuery(id+'display_tag_filter').fadeOut();
        }

      }

      if(jQuery(id+'animation').prop('checked') == true){
        jQuery(id+'display_over_effects').show();
      }

      if(jQuery(id+'force_header_style').prop('checked') == true) {
        jQuery(id + 'header_style').fadeOut();
      } else {
        jQuery(id + 'header_style').fadeIn();
      }

    }

    function category_filter_control(elemento){
      var widget_id = jQuery(elemento).attr('data-wg-id');;
      var category_type = jQuery(elemento).attr('data-type');
      var element_id = jQuery(elemento).attr('id');
      var element_value = jQuery(elemento).val();

      if(category_type == 'include'){
        target_element_name = 'input[data-wg-id='+widget_id+'][data-type="exclude"][value='+element_value+']';
        jQuery(target_element_name).prop('checked', false);
      } else if(category_type == 'exclude'){
        target_element_name = 'input[id^='+widget_id+'][data-type="include"][value='+element_value+']';
        jQuery(target_element_name).prop('checked', false);
      }
    }
    </script>
    <?php
  }
  
  function get_blog_pages($instance){
    $data = get_pages(
          array(
            'sort_order' => 'ASC',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => ( (sizeof($instance['showed_pages']) > 0) ? implode(',', $instance['showed_pages']) : null),
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => $instance['post_number'],
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
          )
        );
    if($instance['animation'] == TRUE){
      $animation = ' animated';
    } else{
      $animation = null;
    }

    foreach ($data as $key => $value) {
        $data[$key]->thumbnail = get_the_post_thumbnail($data[$key]->ID, 'medium',array('class'  => "publication-image".$animation));
        $cable = array();
        $cable['post_date'] = $data[$key]->post_date;
        $cable['ID'] = $data[$key]->ID;
        $cable['post_title'] = $data[$key]->post_title;
        $cable['post_status'] = $data[$key]->post_status;
        $cable['guid'] = $data[$key]->guid;
        $cable['thumbnail'] = $data[$key]->thumbnail;
        
        $result[$key] = $cable;
    }
    return $result;
  }

  function get_custom_posts_list(){
    global $wp_post_types;

    $custom_post_types = get_post_types( array(
        // Set to FALSE to return only custom post types
        '_builtin' => false,
        // Set to TRUE to return only public post types
        'public' => true
    ) );

    $wp_custom_posts = array_keys( $wp_post_types );

    foreach ($wp_custom_posts as $key => $value) {
      if(in_array($value, $custom_post_types)) {
        $tmp['display_name']= $wp_post_types[$value]->labels->name;
        $tmp['post_type_name'] = $value;
        $tmp_custom_post_type[] = $tmp;
      }
    }

    return $tmp_custom_post_type;
  }

  function get_current_time(){
    return current_time('mysql');
  }

  function get_next_update_date($default_update_interval){
    $wp_current_date = $this->get_current_time();
    $update_date_unix = null;
    
    switch ($default_update_interval) {
      case 'current':
        $update_date['unix'] = @date("U");
        $update_date['format'] = @date("F j, Y, g:i:s a");
        break;

      case 'weekly': //Calculate 
        $day_of_week = @date("N", strtotime($wp_current_date));
        $last_day_in_week = @strtotime($wp_current_date.' + '.(7 - $day_of_week).' days');
        
        $update_date['unix'] = @date("U", $last_day_in_week);
        $update_date['format'] = @date("F j, Y, g:i:s a", $last_day_in_week);
        break;

      case 'daily': //Daily update
        // $update_date['unix'] = @date("U", strtotime($wp_current_date.' + 1 day'));
        // $update_date['format'] = @date("F j, Y, g:i:s a", strtotime($wp_current_date.' + 1 day'));
        $update_date['unix'] = date("U", strtotime($wp_current_date));
        $update_date['format'] = date("F j, Y, g:i:s a", strtotime($wp_current_date));
        break;

      case 'monthly': //Monthly update
        
        $current_month = @date("m", strtotime($wp_current_date));
        $year = @date("Y", strtotime($wp_current_date));
        $date = $year.'-'.$current_month.'-01';
        $last_day_in_month = @date("Y-m-t", strtotime($date));

        $update_date['unix'] = @date("U", strtotime($last_day_in_month));
        $update_date['format'] = @date("F j, Y, g:i:s a", @date("U", strtotime($last_day_in_month)) );
        
        break;

      case 'yearly': //Yearly update
        $year = @date("Y", strtotime($wp_current_date));
        $last_day_in_year = $year.'-12-31';

        $update_date['unix'] = @date("U", strtotime($last_day_in_year));
        $update_date['format'] = @date("F j, Y, g:i:s a", @strtotime($last_day_in_year) );
        break;
    }
    
    return $update_date;
  }
  
  /**
   * Return the list of posts that will be displayed on widget, filtered by instance parameters
   *
   * @param array $instance Widget configuration
   *
   * @return array List of posts filtered by $instance parameters
   */
  function get_filtered_posts($instance){
    switch ($instance['content_type']) {
      case 'pages':
        return $this->get_blog_pages($instance);
        break;

      case 'recents':
        return $this->get_recent_posts($instance);
        break;

      case 'posts_by_tag':
        return $this->get_posts_by_tag($instance);
        break;

      case 'custom_post_type':
        return $this->get_posts_by_custom_post_type($instance);
        break;

      case 'populars':
        return $this->get_most_popular_posts($instance);
        break;

      case 'commented':
        return $this->get_most_commented_posts($instance);
        break;
      
      default:
        return NULL;
        break;
    }      
  }

  /**
   * Get a list of the most commented posts
   * @param array $instance Widget configuration
   * @return array List of posts filtered by $instance parameters
   */
  function get_most_commented_posts($instance){
    global $wpdb;
    
    $popular_query = '
    SELECT
      DATE(post_date) AS post_date,
      id,
      post_title,
      post_status,
      guid,
      comment_count AS post_comment_count,
      terms.term_id as cat_id
    FROM ' . $wpdb->prefix . 'posts post INNER JOIN ' . $wpdb->prefix . 'term_relationships rel ON post.ID = rel.object_id
       INNER JOIN ' . $wpdb->prefix . 'term_taxonomy ttax ON rel.term_taxonomy_id = ttax.term_taxonomy_id
       INNER JOIN ' . $wpdb->prefix . 'terms terms ON ttax.term_id = terms.term_id
       INNER JOIN ' . $wpdb->prefix . 'comments ON ' . $wpdb->prefix . 'comments.comment_post_ID = post.ID
    WHERE
      post.post_type = "post"
      AND post.post_status = "publish"
      AND post.post_date > "'.$time_start.'"
      AND ' . $wpdb->prefix . 'comments.comment_approved = 1
      AND ttax.taxonomy = "category"
      AND post.post_date > "'.$time_start.'"
      '.(($instance['included_categories'] != '*' && $instance['included_categories'] > 0) ? 'AND terms.term_id IN('.$instance['included_categories'].')' : NULL).'
    GROUP BY
      id
    ORDER BY
      post_comment_count DESC,
      post_date DESC
    LIMIT ' . $instance['post_number'];

    $data = $wpdb->get_results($popular_query);
    
    if($instance['animation'] == TRUE){
      $animation = ' animated';
    } else{
      $animation = null;
    }
    
    foreach ($data as $key => $value) {
      $data[$key]->thumbnail = get_the_post_thumbnail($data[$key]->id, 'medium',array('class'  => "publication-image".$animation));
      $cable = array();
      $cable['post_date'] = $data[$key]->post_date;
      $cable['ID'] = $data[$key]->id;
      $cable['post_title'] = $data[$key]->post_title;
      $cable['post_status'] = $data[$key]->post_status;
      $cable['guid'] = $data[$key]->guid;
      $cable['post_comment_count'] = $data[$key]->post_comment_count;
      $cable['cat_id'] = $data[$key]->cat_id;
      $cable['thumbnail'] = $data[$key]->thumbnail;
      
      $result[$key] = $cable;
    }
    return $result;
  }

  // Get a list of the most popular posts
  function get_most_popular_posts($instance){
    global $wpdb,$popular_post_table;

    $date_condition = $this->getDateConditionInterval($instance); //DANIEl

    $sql = 'SELECT
        DATE(
          '.$wpdb->prefix.'posts.post_date
        ) post_date,
        '.$wpdb->prefix.'posts.id,
        '.$wpdb->prefix.'posts.post_title,
        '.$wpdb->prefix.'posts.post_status,
        '.$wpdb->prefix.'posts.guid,
        COUNT('.$popular_post_table.'.id) AS view_count
      FROM
        '.$wpdb->prefix.'posts
        INNER JOIN '.$wpdb->prefix.'term_relationships rel ON '.$wpdb->prefix.'posts.ID = rel.object_id
        INNER JOIN '.$wpdb->prefix.'term_taxonomy ttax ON rel.term_taxonomy_id = ttax.term_taxonomy_id
        INNER JOIN '.$wpdb->prefix.'terms terms ON ttax.term_id = terms.term_id
        LEFT JOIN '.$popular_post_table.' ON '.$popular_post_table.'.post_id = '.$wpdb->prefix.'posts.id
      WHERE
        '.$wpdb->prefix.'posts.post_status = "publish"
        '.$date_condition.'
        AND taxonomy = "category"
        '.(($instance['included_categories'] > 0) ? 'AND terms.term_id IN('.$instance['included_categories'].')' : NULL).'
      GROUP BY
        '.$popular_post_table.'.post_id
      ORDER BY
        view_count DESC,
        post_date DESC
      LIMIT '.$instance ['post_number'];

    $data = $wpdb->get_results($sql);

    if($instance['animation'] == TRUE){
      $animation = ' animated';
    } else{
      $animation = null;
    }
    
    //Set thumbnail images gotten from query result
    foreach ($data as $key => $value) {
        $data[$key]->thumbnail = get_the_post_thumbnail($data[$key]->id, 'medium',array('class'  => "publication-image".$animation));
        $cable = array();
        $cable['post_date'] = $data[$key]->post_date;
        $cable['ID'] = $data[$key]->id;
        $cable['post_title'] = $data[$key]->post_title;
        $cable['count'] = $data[$key]->count;
        $cable['view_count'] = $data[$key]->view_count;
        $cable['post_status'] = $data[$key]->post_status;
        $cable['guid'] = $data[$key]->guid;
        $cable['thumbnail'] = $data[$key]->thumbnail;
        
        $result[$key] = $cable;
    }

    return $result;
  }

  function get_post_statistics($instance){
    global $wpdb,$popular_post_table;

    $date_condition = $this->getDateConditionInterval($instance); //DANIEl

    //Get the group of posts id's to update
    $post_ids = array();
    $post_number = sizeof($instance['data']);
    
    for ($i=0; $i < $post_number; $i++) {
      array_push($post_ids, $instance['data'][$i]['ID']);
    }

    $post_id_collection = implode(',', $post_ids);
    
    $sql = '
    SELECT
      pp.post_id,
      COUNT(pp.id) view_count
    FROM
      `'.$popular_post_table.'` pp
    WHERE
      post_id IN('.$post_id_collection.')
      '.$date_condition.'
    GROUP BY post_id
    ORDER BY pp.post_id DESC';

    $post_stats = $wpdb->get_results($sql);
    $tmp_post_data = array_shift($post_stats);

    for($i=0; $i < $post_number; $i++) {
      
      if(isset($tmp_post_data) && intval($instance['data'][$i]['ID']) == intval($tmp_post_data->post_id)){
        
        $instance['data'][$i]['view_count'] = intval($tmp_post_data->view_count);
        $tmp_post_data = array_shift($post_stats);

      } else {
        $instance['data'][$i]['view_count'] = 0;
      }
    }

    return $instance['data'];
  }

  function get_posts_by_tag($instance){
    
    $filtered_result = new WP_Query(
                            array(
                              'tag__in' => $instance['tag_filter'],
                              'posts_per_page' => $instance['post_number'],
                              'nopaging' => false,
                              'category__in' => $instance['category_filter'],
                              'category__not_in' => $instance['exclude_category_filter']
                            )
                      );
    
    $i = 0;
    $tmp_post = array();
    if($instance['animation'] == TRUE){
      $animation = ' animated';
    } else{
      $animation = null;
    }
    
    foreach ($filtered_result->posts as $post) {

        $tmp_post[$i]['post_date'] = $post->post_date;
        $tmp_post[$i]['ID'] = $post->ID;
        $tmp_post[$i]['post_title'] = $post->post_title;
        $tmp_post[$i]['count'] = $post->count;
        $tmp_post[$i]['view_count'] = $post->view_count;
        $tmp_post[$i]['post_status'] = $post->post_status;
        $tmp_post[$i]['guid'] = $post->guid;
        $tmp_post[$i]['thumbnail'] = get_the_post_thumbnail($post->ID, 'medium',array('class'  => "publication-image".$animation));

        $i++;

    }

    return $tmp_post;
  }

  function getDateConditionInterval($instance){
    global $popular_post_table;
    
    $date_filter_sql = null;
    $limit_interval = $instance['period_interval'];

    switch ($limit_interval) {
      case 'daily':
        $date_filter_sql = 'AND DATE('.$popular_post_table.'.date) = \''.gmdate("Y-m-d", $instance['limit_content_date']['unix']).'\' #MOST POPULAR FOR TODAY';
        break;
      case 'weekly':
        $first_day_of_week = $this->getFirstDayInWeek($instance['limit_content_date']['format']);
        $date_filter_sql = 'AND DATE('.$popular_post_table.'.date) BETWEEN \''.gmdate("Y-m-d", $first_day_of_week['unix'] ).'\' AND \''.gmdate("Y-m-d", $instance['limit_content_date']['unix']).'\' #MOST POPULAR ON A CERTAIN WEEK';
      break;
      case 'monthly':
        $date_filter_sql = 'AND MONTH('.$popular_post_table.'.date) = \''.gmdate("m", $instance['limit_content_date']['unix']).'\'  #MOST POPULAR FOR THIS MONTH';
      break;
      case 'yearly':
        $date_filter_sql = 'AND YEAR('.$popular_post_table.'.date) = '.gmdate("Y", $instance['limit_content_date']['unix']).' #MOST POPULAR POSTS THIS YEAR';
      break;
    }

    return $date_filter_sql;
  }

  function getFirstDayInWeek(){
    $current_date = $this->get_current_time();
    $day_of_week = @date("N", strtotime($current_date));
    
    if($day_of_week > 1){
      $last_day_in_week = @strtotime($current_date.' - '.$day_of_week.' days');
    } else {
      $last_day_in_week = @strtotime($current_date);
    }
    
    $first_day['unix'] = @date("U", $last_day_in_week);
    $first_day['format'] = @date("F j, Y, g:i:s a", $last_day_in_week);

    return $first_day;
  }

  // Get Post Published recently on current blog based on instance configuration
  function get_recent_posts($instance){
    // exit;
    if(isset($instance)) {
      $data = wp_get_recent_posts(
                array(
                  'numberposts' => $instance['post_number'],
                  'offset' => 0,
                  'category' => ((sizeof($instance['included_categories']) > 0) ? $instance['included_categories'] : NULL),
                  'orderby' => 'id',
                  'order' => 'DESC',
                  'include' => NULL,
                  'exclude' => NULL,
                  'meta_key' => NULL,
                  'meta_value' => NULL,
                  'post_type' => 'post',
                  'post_status' => 'publish',
                  'suppress_filters' => true
                )
              );

      if($instance['animation'] == TRUE){
        $animation = ' animated';
      } else{
        $animation = null;
      }
      
      foreach ($data as $key => $value) {
        $data[$key]['thumbnail'] = get_the_post_thumbnail($data[$key]['ID'], 'medium',array('class'  => "publication-image".$animation));
      }
      
      $instance['data'] = $data;
      $data = $this->get_post_statistics($instance);
      
      return $data;
    }
    return NULL;
  }

  function get_posts_by_custom_post_type($instance){
    if(isset($instance)) {
      $data = wp_get_recent_posts(
                array(
                  'numberposts' => $instance['post_number'],
                  'offset' => 0,
                  'category' => ((sizeof($instance['included_categories']) > 0) ? $instance['included_categories'] : NULL),
                  'orderby' => 'id',
                  'order' => 'DESC',
                  'include' => NULL,
                  'exclude' => NULL,
                  'meta_key' => NULL,
                  'meta_value' => NULL,
                  'post_type' => $instance['custom_post_types'],
                  'post_status' => 'publish',
                  'suppress_filters' => true )
              );

      $instance['data'] = $data;
      $data = $this->get_post_statistics($instance);
      
      if($instance['animation'] == TRUE){
        $animation = ' animated';
      } else{
        $animation = null;
      }

      foreach ($data as $key => $value) {
        $data[$key]['thumbnail'] = get_the_post_thumbnail($data[$key]['ID'], 'medium',array('class'  => "publication-image".$animation));
      }

      return $data;
    }

    return NULL;
  }

  /**
   * Register a unique post pageview on database
   */
  public static function register_view(){
    global $wpdb, $post, $popular_post_table, $mostviewedposts_count_once;
    if(is_single() && !is_page() && empty($mostviewedposts_count_once)){
      $wpdb->get_results("INSERT INTO " .  $popular_post_table . " (id, post_id, date) VALUES (NULL, " . $post->ID . ", '" .date("c", current_time('timestamp', 0)). "')");
      $mostviewedposts_count_once = 1;
    }
  }    

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
      $instance = array();
      
      $title = $this->getWidgetTitle($new_instance['content_type']);
      $instance['title'] = ( ! empty( $new_instance['title'] ) AND $new_instance['title'] <> "" ) ? strip_tags( $new_instance['title'] ) : $title;
      $instance['title_color'] = $new_instance['title_color'];
      $instance['title_font_family'] = (isset($new_instance['title_font_family']) && strlen($new_instance['title_font_family']) > 0 ? $new_instance['title_font_family'] : 'Open Sans Condensed');
      $instance['title_font_size'] = $new_instance['title_font_size'];
      $instance['title_font_color'] = $new_instance['title_font_color'];
      
      $instance['width'] = $new_instance['width'];
      $instance['height'] = $new_instance['height'];

      $instance['background_color'] = $new_instance['background_color'];
      $instance['background_color2'] = $new_instance['background_color2'];
      $instance['font_color'] = $new_instance['font_color'];
      $instance['date_color'] = $new_instance['date_color'];
      $instance['widget_body_font_family'] = (isset($new_instance['widget_body_font_family']) && strlen($new_instance['widget_body_font_family']) > 0 ? $new_instance['widget_body_font_family'] : 'Open Sans Condensed');
      $instance['font_date_color'] = $new_instance['font_date_color'];
      $instance['widget_body_font_size'] = $new_instance['widget_body_font_size'];
      $instance['effect'] = $new_instance['effect'];
      
      $instance['displaycategory'] = (isset($new_instance['displaycategory']) AND $new_instance['displaycategory'] == 1) ? $new_instance['displaycategory'] : 0;
      $instance['displaydate'] = (isset($new_instance['displaydate']) AND $new_instance['displaydate'] == 1) ? $new_instance['displaydate'] : 0;
      $instance['showcounter'] = (isset($new_instance['showcounter']) AND $new_instance['showcounter'] == 1) ? $new_instance['showcounter'] : 0;
      $instance['bg_gradient'] = (isset($new_instance['bg_gradient']) AND $new_instance['bg_gradient'] == 1) ? $new_instance['bg_gradient'] : 0;
      $instance['force_header_style'] = (isset($new_instance['force_header_style']) AND $new_instance['force_header_style'] == TRUE) ? $new_instance['force_header_style'] : 0;
      $instance['fixed_background'] = (isset($new_instance['fixed_background']) AND $new_instance['fixed_background'] == 'TRUE') ? TRUE : FALSE;
      $instance['animation'] = (isset($new_instance['animation']) AND $new_instance['animation'] == TRUE) ? $new_instance['animation'] : 0;
      
      $instance['content_type'] = ( ! empty( $new_instance['content_type'] ) ) ? strip_tags( $new_instance['content_type'] ) : '';
      
      $instance['display_advance_filters'] = ( ! empty( $new_instance['display_advance_filters'] ) ) ? strip_tags( $new_instance['display_advance_filters'] ) : '';
      
      $instance['post_number'] = ( ! empty( $new_instance['post_number'] ) ) ? strip_tags( $new_instance['post_number'] ) : 10;
      
      if(isset($new_instance['content_type']) AND $instance['content_type'] == 'custom_post_type') {
        $instance['custom_post_types'] = $new_instance['custom_post_types'];
      } else {
        unset($instance['custom_post_types']);
      }
      
      if(isset($new_instance['content_type']) AND $new_instance['content_type'] == 'pages' OR empty($new_instance['content_type'])) {
        $instance['showed_pages'] = $new_instance['showed_pages'];
        
        unset($instance['included_categories']);
        unset($instance['category_filter']);
        unset($instance['tag_filter']);
        unset($instance['exclude_category_filter']);

      } else {
        
        //Concatenate categories that will be shown on widget.
        $tmp_included_categories = '*';
        for ($i=0; $i < sizeof($new_instance['category_filter']); $i++) {
          $tmp_included_categories = $tmp_included_categories.','.$new_instance['category_filter'][$i];
        }
        
        for ($i=0; $i < sizeof($new_instance['exclude_category_filter']); $i++) {
          $tmp_included_categories = $tmp_included_categories.',-'.$new_instance['exclude_category_filter'][$i];
        }
        $tmp_included_categories = str_replace('*,',null, $tmp_included_categories); //List categories that will be displayed on widget
        
        $instance['included_categories'] = $tmp_included_categories;
        $instance['category_filter'] = $new_instance['category_filter'];
        $instance['tag_filter'] = $new_instance['tag_filter'];
        $instance['exclude_category_filter'] = $new_instance['exclude_category_filter'];
        unset($instance['showed_pages']);

      }

      //Sets first instance update intervals for widget and cache
      if( isset($new_instance['content_type']) AND ( $new_instance['content_type'] == 'populars' OR $new_instance['content_type'] == 'commented' ) ){

        //Update data intervals only when content is popular posts or most commented
        $instance['period_interval'] = $new_instance['period_interval']; //daily, weekly, monthly
        $instance['stats_period_interval'] = $new_instance['stats_period_interval']; //Update cache every
        
        //Set the variable that will be updating content over widget // Set the variable that will be delimiting cache 
        $instance['limit_stats_date']['unix'] = @date("U", strtotime(current_time('mysql').$instance['stats_period_interval']));
        $instance['limit_stats_date']['format'] = @date("F j, Y, g:i:s a", strtotime(current_time('mysql').$instance['stats_period_interval']));

        //DEBUG
        // $instance['limit_stats_date']['unix'] = @date("U", strtotime(current_time('mysql').'+1 Seconds'));
        // $instance['limit_stats_date']['format'] = @date("F j, Y, g:i:s a", strtotime(current_time('mysql').'+1 Seconds') );

        $instance['limit_content_date'] = $this->get_next_update_date($instance['period_interval']);

      }

      $instance['data'] = $this->get_filtered_posts($instance);
      
      return $instance;
  }

  function set_up_custom_font_family($instance){
    
    wp_register_style( 'kbl_top_stories_widget_font', 
       'http://fonts.googleapis.com/css?family='.$instance['title_font_family'],
       array(),
       '3.0',
       'screen' );
    wp_enqueue_style( 'kbl_top_stories_widget_font' );

    wp_register_style( 'kbl_top_stories_widget_font_body', 
       'http://fonts.googleapis.com/css?family='.$instance['widget_body_font_family'],
       array(),
       '3.0',
       'screen' );
    wp_enqueue_style( 'kbl_top_stories_widget_font_body' );
  }

  
  function getWidgetTitle($content_type){
    //Return a default widget title depending on the content
    $title = null;
    
    switch ($content_type) {
      case 'pages':
        $title = 'Pages';
        break;

      case 'recents':
        $title = 'Recent Posts';
        break;

      case 'populars':
        $title = 'Most Popular Posts';
        break;

      case 'commented':
        $title = 'Most Commented Posts';
        break;
      
      case 'custom_post_type':
        $title = 'Our Top Posts Content';
        break;
      
      default:
        $title = 'Top Stories Widget';
        break;
    }

    return $title;
  }

  /**
   * Display configured widget on sidebar blog
   *
   * @see WP_Widget::widget
   *
   * @param array $args Configuration values for widget like name or id.
   * @param array $instances Configuration specified by user on dashboard like color, font color etcetera. 
   *
   */
  function widget($args, $instance) {

    // echo "<pre>";
    // print_r($instance['fixed_background']);
    // print_r($instance['fixed_background']);
    // echo "</pre>";
    
    $this->set_up_custom_font_family($instance);
    
    $current_date['unix'] = @date("U", strtotime(current_time('mysql')));
    $current_date['format'] = @date("F j, Y, g:i:s a", strtotime(current_time('mysql')));

    //Update widget content if limit date cache expired
    if( $current_date['unix'] > $instance['limit_stats_date']['unix'] ){ //Widget content needs to be updated

      $widget_instances = get_option('widget_DGT_TopStories');
      $widget_instance_id = substr(strrchr($args['widget_id'], "-"), 1); //The instance id want to update

      //Set the variable that will be updating content over widget // Set the variable that will be delimiting cache 
      $widget_instances[$widget_instance_id]['limit_stats_date']['unix'] = @date("U", strtotime(current_time('mysql').$instance['stats_period_interval']));
      $widget_instances[$widget_instance_id]['limit_stats_date']['format'] = @date("F j, Y, g:i:s a", strtotime(current_time('mysql').$instance['stats_period_interval']));
      
      //DEBUG
      // $widget_instances[$widget_instance_id]['limit_stats_date']['unix'] = @date("U", strtotime($this->get_current_time().'+1 Seconds'));
      // $widget_instances[$widget_instance_id]['limit_stats_date']['format'] = @date("F j, Y, g:i:s a", strtotime($this->get_current_time().'+1 Seconds'));

      $widget_instances[$widget_instance_id]['data'] = $this->get_filtered_posts($instance); //Update widget content according widget configuration panel

      if($current_date['unix'] > $instance['limit_content_date']){ //Limit stats range date is updated
        $widget_instances[$widget_instance_id]['limit_content_date'] = $this->get_next_update_date($instance['period_interval']); //Sets a new date for filter
      }

      update_option('widget_DGT_TopStories',$widget_instances);

    }

    extract( $args );

    $title = apply_filters('widget_title', $instance['title']);
    echo $before_widget; 
      $month_names = array('JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER');
      $style = NULL;
      $most_viewed_posts = $instance['data'];
      
      if(isset($instance['title_font_family']) OR strtoupper($instance['title_font_color']) !='#FFFFFF' OR $instance['title_color'] != '#000000' OR isset($instance['width']) OR $instance['title_font_size'] > 0) {
        $style = ' style="';
        $style .= ((strtoupper($instance['title_font_family']) != '') ? 'font-family: \''.urldecode($instance['title_font_family']).'\' !important;' : NULL);
        $style .= ((strtoupper($instance['title_font_color']) != '#FFFFFF') ? 'color:'.$instance['title_font_color'].';' : NULL);
        $style .= (($instance['title_color'] != '#000000') ? 'background-color:'.$instance['title_color'].';' : NULL);
        $style .= (($instance['title_font_size'] > 0) ? 'font-size:'.$instance['title_font_size'].'px !important;' : NULL);
        $style .= ((isset($instance['width']) && $instance['width'] > 0) ? 'width:'.($instance['width']).'px' : NULL);
        $style .= '"';
      }

      if( $instance['force_header_style'] == FALSE && $title ){
        echo $before_title . '<h3 class="head-widget"'.$style.'>'. strtoupper($instance['title']) .'</h3>' . $after_title; 
      } else {
        echo $before_title.$instance['title'].$after_title;
      }
      $html_widget = NULL;
      unset($style);

      $background_gradient = NULL;
      if( $instance['bg_gradient'] == TRUE){
        $background_gradient = 'background: linear-gradient(-45deg, '.$instance['background_color'].' 0%,'.$instance['background_color2'].' 100%);';
      }

      $html_widget .= '<ul class="popular-publications" style="margin: 0; padding:0; '.((isset($instance['width']) AND $instance['width'] > 0) ? 'width:'.$instance['width'].'px' : NULL).'">';
      if(sizeof($instance['data']) > 0){
        foreach($instance['data'] as $post){
          
          // if(!(function_exists('current_theme_supports') && current_theme_supports('post-thumbnails'))){
          //   $html_widget .= "ERROR: Your current installation don't support image thumbnails. Please Update Your Wordpress Version";
          // }
          
          $html_widget .= '<li class="itm effect '.$instance['effect'].'" style="background-color:'.$instance['background_color'].'; '.$background_gradient.'">';
            $html_widget .= '<a href="'.get_permalink($post['ID']).'" class="buble-link">';
              
              if(has_post_thumbnail($post['ID'])) {
                
                if(isset($instance['height']) || isset($instance['width']) || isset($instance['fixed_background']) ) {
                  
                  if(is_numeric($instance['height']) && $instance['height'] > 0){
                    $style_attribute .= 'min-height:'.$instance['height'].'px !important; ';
                  }

                  if(isset($instance['width']) && is_numeric($instance['width']) && $instance['width'] > 0 && $instance['fixed_background'] == FALSE) {
                    $style_attribute .= 'width:'.$instance['width'].'px !important; ';
                  } else if($instance['fixed_background'] == TRUE) {
                    $style_attribute .= 'width:100% !important; ';
                  }

                  $style_attribute = 'style="'.$style_attribute.'"';

                  $html_widget .= preg_replace('/(width|height)="[0-9]*"/i', $style_attribute, $post['thumbnail']);

                // } else if($instance['fixed_background'] == FALSE) {
                } else {
                  $html_widget .= $post['thumbnail'];
                }

              }
              
              // $attachment_data = decode_tfuse_options(get_post_meta($post['ID'], TF_THEME_PREFIX .'_tfuse_post_options', true), true);
              // $image_url = $attachment_data['newssetter_thumbnail_image'];
              // $html_widget .= '<img src="'.$image_url.'" class="publication-image wp-post-image" width="100%">';
              $height_widget_style = (isset($instance['height']) && $instance['height'] > 0 ? 'style="min-height: '.$instance['height'].'px;"' : null);

              $html_widget .= '<div class="publication-over" '.$height_widget_style.' >';
                if(($instance['displaydate'] == 1 OR $instance['displaycategory'] == 1 OR $instance['showcounter'] == 1) AND $instance['content_type'] <> 'pages'){
                  $html_widget .= '<div class="meta">';
                    
                    if($instance['displaycategory'] == 1) {
                      $post_category = get_the_category($post['ID']); 
                      $html_widget .= '<span class="publication-date" style="background-color: '.$instance['date_color'].';color:'.$instance['font_date_color'].';">'.(($instance['content_type'] == 'custom_post_type') ? $post['post_type'] : trim($post_category[0]->cat_name) ).'</span>';
                    } elseif($instance['displaydate'] == 1) {
                      list($year, $month, $day) = explode("-", $post['post_date']);
                      $html_widget .= '<span class="publication-date" style="background-color:'.$instance['date_color'].';color:'.$instance['font_date_color'].';">'.$month_names[($month-1)].' '.trim(substr($day,0, 2)).', '.$year.'</span>';
                    }
                                
                    $html_widget .= '<span class="comment-count">';
                    
                    if(($instance['content_type'] == "populars" || $instance['content_type'] == "recents" || $instance['content_type'] == "commented") && $instance['showcounter'] == 1) {
                      $html_widget .= '<span style="color:'.$instance['font_date_color'].';">'.number_format(($instance['content_type'] == 'commented' ? $post['post_comment_count'] : $post['view_count'])).'</span>';
                      $html_widget .= '<div class="buble-link">';
                        $html_widget .= '<i class="counter '.($instance['content_type'] == 'commented' ? 'cmnts' : 'views').'">&nbsp;&nbsp;&nbsp;&nbsp;</i>';
                      $html_widget .= '</div>';
                    } else {
                      $html_widget .= '&nbsp;';
                    }

                    $html_widget .= '</span>'; //End span.comment-count
                  $html_widget .= '</div>'; //END DIV META
                }

                $html_widget .= '<h3 class="feature-title" style="color:'.$instance['font_color'].';font-family: \''.urldecode($instance['widget_body_font_family']).'\' !important;font-size:'.( ($instance['widget_body_font_size'] > 0) ? $instance['widget_body_font_size'] : '23' ).'px !important;">';
                  $html_widget .= $post['post_title'];
                $html_widget .= '</h3>';

              $html_widget .= '</div>'; //End div.publication-over
            $html_widget .= '</a>';
          $html_widget .= '</li>';                  
        }
      } else {
        echo 'Upss there are no posts that meet specified criteria, please verify widget configuration and ensure that ';
      }
      $html_widget .= '</ul>';
      echo $html_widget;
    echo $after_widget;
  }
} // class DGT_TopStories

function kbl_top_stories_lang_init() {
  if( is_admin() ) {
    $locale = apply_filters( 'plugin_locale', get_locale(), 'top_stories' );
    load_textdomain( 'top_stories', dirname( __FILE__ ) . "/i18n/top_stories-$locale.mo" );
  }
}

add_action('admin_init', 'kbl_top_stories_lang_init');

add_action('widgets_init', create_function('', 'return register_widget("DGT_TopStories");'));

function kbl_top_stories_scripts_loaded() {
  wp_register_style( 'kbl_top_stories_widget_style',
		 	plugins_url( 'css/style.css', __FILE__ ),
		 	array(),
		 	'.1',
		 	'screen' );
  wp_enqueue_style( 'kbl_top_stories_widget_style' );
}
add_action('wp_head', array('DGT_TopStories', 'register_view'));
add_action( 'wp_enqueue_scripts', 'kbl_top_stories_scripts_loaded' ); // wp_enqueue_scripts action hook to link only on the front-end
register_activation_hook( __FILE__, array('DGT_TopStories', 'top_stories_activate'));
register_deactivation_hook( __FILE__, array('DGT_TopStories', 'top_stories_deactivate'));