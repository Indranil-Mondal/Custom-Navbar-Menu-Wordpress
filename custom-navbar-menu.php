<?php
/**
 * Plugin Name: Custom Arrow Navbar Menu
 * Description: Adds a shortcode [custom_arrow_menu] to render a WordPress menu with a custom right arrow (SVG) before each item. Submenus are nested <ul class="sub-menu"> and are expandable/collapsible ONLY by clicking the arrow icon. Clicking the text/link follows the URL if it exists (not # or empty). Works at any depth. Clean class names, no external dependencies. Useful for builders that have no Menu widget
 * Version: 1.1.0
 * Author: Indranil Monda;
 * Author URI: https://github.com/Indranil-Mondal
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Custom_Arrow_Menu_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "\n$indent<ul class=\"sub-menu\">\n";
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "$indent</ul>\n";
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes );

        // Top-level items get nav-item class
        if ( $depth === 0 ) {
            $classes[] = 'nav-item';
        }

        if ( $has_children ) {
            $classes[] = 'has-submenu';
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $id_attr = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
        $id_attr = $id_attr ? ' id="' . esc_attr( $id_attr ) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        // Base link attributes
        $attributes  = ! empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) . '"' : '';
        $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target )     . '"' : '';
        $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn )        . '"' : '';

        // Determine link class (nav-link for top level, dropdown-item for sublevels)
        $link_class = ( $depth === 0 ) ? 'nav-link' : 'dropdown-item';

        // Check if the menu item has a real URL (not empty and not '#')
        $item_url = ! empty( $item->url ) ? esc_attr( $item->url ) : '';
        $has_real_link = ( $item_url !== '' && $item_url !== '#' );

        // Set href: real URL if exists, otherwise javascript:void(0) to prevent scrolling/jumping
        if ( $has_real_link ) {
            $attributes .= ' href="' . $item_url . '"';
        } else {
            $attributes .= ' href="javascript:void(0)"';
        }

        // If it's a parent with no real link, add a class so we can style/toggle the whole item if needed
        if ( $has_children && ! $has_real_link ) {
            $link_class .= ' dummy-parent';
        }

        $attributes .= ' class="' . esc_attr( $link_class ) . '"';

        // The exact SVG arrow as requested
        $svg = '<svg class="menu-arrow" width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6.84733 5.5105L0.196598 11.2566L0.00011284 -4.17224e-06L6.84733 5.5105Z" fill="#D6DF23"/>
                </svg>';

        $item_output = isset( $args->before ) ? $args->before : '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= $svg;
        $item_output .= isset( $args->link_before ) ? $args->link_before : '';
        $item_output .= apply_filters( 'the_title', $item->title, $item->ID );
        $item_output .= isset( $args->link_after ) ? $args->link_after : '';
        $item_output .= '</a>';
        $item_output .= isset( $args->after ) ? $args->after : '';

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }
}

// Shortcode: [custom_arrow_menu menu="your-menu-slug" ] or [custom_arrow_menu theme_location="primary"]
function custom_arrow_menu_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'menu'            => '',
        'theme_location'  => '',
        'menu_class'      => 'navbar-nav',
    ), $atts, 'custom_arrow_menu' );

    if ( empty( $atts['menu'] ) && empty( $atts['theme_location'] ) ) {
        return '<!-- Custom Arrow Menu: No menu or theme location specified -->';
    }

    $menu_args = array(
        'echo'            => false,
        'container'       => false,
        'menu_class'      => $atts['menu_class'],
        'walker'          => new Custom_Arrow_Menu_Walker(),
        'fallback_cb'     => '__return_false',
    );

    if ( ! empty( $atts['menu'] ) ) {
        $menu_args['menu'] = $atts['menu'];
    } else {
        $menu_args['theme_location'] = $atts['theme_location'];
    }

    return wp_nav_menu( $menu_args );
}
add_shortcode( 'custom_arrow_menu', 'custom_arrow_menu_shortcode' );

// Inline CSS
function custom_arrow_menu_add_styles() {
    ?>
    <style type="text/css">
        /* Hide submenus by default */
		ul.navbar-nav li{
			list-style-type: none;
		}
		
		ul.navbar-nav > .menu-item {
            font-size: 28px; 
		}
		
		ul.sub-menu{
			padding-left: 6px;
			padding-top: 10px;
			font-size: max(16px, 0.75em);
		}
		
		.navbar-nav a.nav-link:hover{
			color: #D6DF23;
		}
		
        .sub-menu {
            display: none;
        }

        .has-submenu.open > .sub-menu {
            display: block;
        }
		
		

        /* Arrow and dummy parent styling */
        .menu-arrow,
        a.dummy-parent {
            cursor: pointer;
            display: inline-block;
            margin-right: 15px;
            transition: transform 0.3s ease;
            vertical-align: middle;
        }

        .has-submenu.open > a > .menu-arrow {
            transform: rotate(90deg); /* right → down */
        }

        /* Desktop dropdown positioning (Bootstrap-like) */
       .navbar-nav > .has-submenu > .sub-menu {                
			padding: 0px 0px 0px 6px;
			margin: 0; 
		}

		.navbar-nav > .has-submenu > .sub-menu li{                
			padding: 5px 0px 5px 0px;

		}

		.sub-menu .has-submenu > .sub-menu {

		}
        

        /* Dropdown item basic styling */
        .dropdown-item {
            display: block;
            width: 100%;                        
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;  
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: #D6DF23;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'custom_arrow_menu_add_styles' );

// Inline JavaScript – toggle ONLY on arrow click (or whole item if no real link)
function custom_arrow_menu_add_scripts() {
    ?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                // 1. Click directly on the arrow icon → toggle submenu
                const arrow = e.target.closest('.menu-arrow');
                if (arrow) {
                    const li = arrow.closest('.has-submenu');
                    if (li) {
                        e.preventDefault();
                        e.stopPropagation();
                        li.classList.toggle('open');
                        return;
                    }
                }

                // 2. Click on a parent item with NO real link (# or empty) → toggle submenu
                const dummyLink = e.target.closest('a.dummy-parent');
                if (dummyLink) {
                    e.preventDefault();
                    const li = dummyLink.closest('.has-submenu');
                    if (li) {
                        li.classList.toggle('open');
                    }
                    return;
                }

                // 3. Click outside the menu → close all open submenus
                if (!e.target.closest('.navbar-nav')) {
                    document.querySelectorAll('.navbar-nav .has-submenu.open').forEach(function (el) {
                        el.classList.remove('open');
                    });
                }
            });
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'custom_arrow_menu_add_scripts' );