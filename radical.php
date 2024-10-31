<?php

define( 'WP_RADICAL_PLUGIN__FILE__', __FILE__ );

/**
 * Plugin Name: Radical
 * Version: 1.0
 * Description: Use Radical to block spam comments
 * Author: AQP hosting
 * Author URI: https://alojamientowp.org/
 * Plugin URI: https://wordpress.org/plugins/radical
 */

if ( ! class_exists( 'WPRadicalPlugin' ) )
{
    class WPRadicalPlugin
    {
        public function __construct()
        {
            $this->setup_actions();
        }
        
        public function setup_actions()
        {
            add_action( 'comment_post', array($this, 'update_comment_status'), 10, 1 );
            register_activation_hook( WP_RADICAL_PLUGIN__FILE__, array($this, 'analyze_old_comments') );
        }

        public function analyze_old_comments()
        {
            foreach (get_comments() as $comment)
            {
                $this->update_comment_status($comment->comment_ID);
            }
        }

        public function update_comment_status($comment_ID)
        {
            $comment_ID = intval($comment_ID);
            $comment = get_comment($comment_ID);
            if (!empty($comment->comment_author_url) || (!empty($comment->comment_content) &&
                (preg_match('/(http|mailto)/i', $comment->comment_content) || 
                 filter_var($comment->comment_content, FILTER_VALIDATE_URL))
                ) || $this->checkIfContainsDomainName($comment->comment_content)
            ) {
                do_action( 'spam_comment', $comment_ID, $comment );
                if ( wp_set_comment_status( $comment, 'spam' ) )
                {
                    delete_comment_meta( $comment_ID, '_wp_trash_meta_status' );
                    delete_comment_meta( $comment_ID, '_wp_trash_meta_time' );
                    add_comment_meta( $comment_ID, '_wp_trash_meta_status', $comment->comment_approved );
                    add_comment_meta( $comment_ID, '_wp_trash_meta_time', time() );
                    do_action( 'spammed_comment', $comment_ID, $comment );
                }
            }
        }

        private function checkIfContainsDomainName($string)
        {
            $pattern = '/(http[s]?\:\/\/)?(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{2,63}/';
            return preg_match($pattern, $string);
        }
    }

    $wp_plugin_comentarios_plugin = new WPRadicalPlugin();
}