<?php
/**
 * FooWidget Class
 */
class KohanaWidget extends WP_Widget {
    /** constructor */
    function KohanaWidget() {
        parent::WP_Widget(false, $name = 'KohanaWidget');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        ?>
              <?php echo $before_widget; ?>
                  <?php 
                  if( $instance['title'] ){
                  echo $before_title
                      . $instance['title']
                      . $after_title; 
                  }
                   ?>
                  <?php kohana($instance['kohana_request']) ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        $req = esc_attr($instance['kohana_request']);
        ?>
            <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
            <?php _e('Title:'); ?> 
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            </label>
            <label for="<?php echo $this->get_field_id('kohana_request'); ?>">
            <?php _e('Kohana Request:'); ?> 
            <input class="widefat" id="<?php echo $this->get_field_id('kohana_request'); ?>" name="<?php echo $this->get_field_name('kohana_request'); ?>" type="text" value="<?php echo $req; ?>" />
            </label>
            </p>
        <?php 
    }

} // class KohanaWidget
