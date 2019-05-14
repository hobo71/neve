<?php
/**
 * Header View Manager
 *
 * @package Neve\Views
 */

namespace Neve\Views;

use HFG\Core\Components\Nav;

/**
 * Class Header
 *
 * @package Neve\Views
 */
class Header extends Base_View {
	/**
	 * Add hooks for the front end.
	 */
	public function init() {
		add_filter( 'wp_nav_menu_items', array( $this, 'add_last_menu_item' ), 10, 2 );
		add_filter( 'wp_page_menu', array( $this, 'add_fallback_last_menu_items' ), 10, 2 );
	}

	/**
	 * Add the last menu item.
	 *
	 * @param string $items the nav menu markup.
	 * @param object $args  menu properties.
	 *
	 * @return string
	 */
	public function add_last_menu_item( $items, $args ) {
		if ( $args->theme_location !== 'primary' ) {
			return $items;
		}

		$items = $this->get_last_menu_items_markup( $items );

		return $items;
	}

	/**
	 * Get the last menu items.
	 *
	 * @param string $items the menu markup.
	 *
	 * @return string
	 */
	private function get_last_menu_items_markup( $items = '' ) {
		$additional_items = $this->get_last_menu_item_setting();
		$additional_items = json_decode( $additional_items, true );
		if ( empty( $additional_items ) ) {
			return $items;
		}
		foreach ( $additional_items as $item ) {
			if ( $item === 'search' ) {
				$items .= $this->get_nav_menu_search();
			} else if ( $item === 'cart' ) {
				$items .= $this->get_nav_menu_cart();
			} else {
				$items .= apply_filters( 'neve_last_menu_item_'. $item, '' );
			}
		}

		return apply_filters( 'neve_last_menu_item', $items );
	}

	/**
	 * Get the last menu item theme mod value.
	 *
	 * @return string
	 */
	private function get_last_menu_item_setting() {
		$default = array(
			'search',
		);
		if ( class_exists( 'WooCommerce' ) ) {
			array_push( $default, 'cart' );
		}

		$current_component = 'default';
		if ( isset( Nav::$current_component ) ) {
			$current_component = Nav::$current_component;
		}
		$last_menu_setting_slug = apply_filters( 'neve_last_menu_setting_slug_' . $current_component, 'neve_last_menu_item' );

		return get_theme_mod( $last_menu_setting_slug, $default );
	}

	/**
	 * Get the markup for the nav menu search.
	 *
	 * @param bool $responsive should get the responsive version.
	 *
	 * @return string
	 */
	private function get_nav_menu_search( $responsive = false ) {
		// TODO when HFG is live we should drop this at all as we have a section for icon, or offer a way of disabling it.
		$tag   = 'li';
		$class = 'menu-item-nav-search';
		if ( $responsive === true ) {
			$tag = 'span';

			$class .= ' responsive-nav-search ';
		}
		$search = '';

		$search .= '<' . esc_attr( $tag ) . ' class="' . esc_attr( $class ) . '" tabindex="0" aria-label="search">';
		$search .= '<a>' . neve_search_icon() . '</a>';
		$search .= '<div class="nv-nav-search">';
		if ( $responsive === true ) {
			$search .= '<div class="container close-container">';
			$search .= '<a class="button button-secondary close-responsive-search">' . __( 'Close', 'neve' ) . '</a>';
			$search .= '</div>';
		}
		$search .= get_search_form( false );
		$search .= '</div>';
		$search .= '</' . esc_attr( $tag ) . '>';

		return $search;
	}

	/**
	 * Get the markup for the nav menu cart.
	 *
	 * @param bool $responsive should get the responsive version.
	 *
	 * @return string
	 */
	private function get_nav_menu_cart( $responsive = false ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return '';
		}

		$tag   = 'li';
		$class = 'menu-item-nav-cart';
		if ( $responsive === true ) {
			$tag = 'span';

			$class .= ' responsive-nav-cart ';
		}
		$cart = '';

		$cart .= '<' . esc_attr( $tag ) . ' class="' . esc_attr( $class ) . '"><a href="' . esc_url( wc_get_cart_url() ) . '" class="cart-icon-wrapper">';
		$cart .= neve_cart_icon();
		$cart .= '<span class="screen-reader-text">' . __( 'Cart', 'neve' ) . '</span>';
		$cart .= '<span class="cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
		$cart .= '</a>';

		if ( is_cart() || is_checkout() ) {
			$cart .= '</' . esc_attr( $tag ) . '>';

			return $cart;
		}

		if ( $responsive === false ) {
			ob_start();
			echo '<div class="nv-nav-cart">';
			do_action( 'neve_before_cart_popup' );
			the_widget(
				'WC_Widget_Cart',
				array(
					'title' => ' ',
				),
				array(
					'before_title' => '',
					'after_title'  => '',
				)
			);
			do_action( 'neve_after_cart_popup' );
			echo '</div>';
			$cart_widget = ob_get_contents();
			ob_end_clean();
			$cart .= $cart_widget;
		}
		$cart .= '</' . esc_attr( $tag ) . '>';

		return $cart;
	}

	/**
	 * Add last menu items to fallback menu.
	 *
	 * @param string $menu the menu markup.
	 * @param array  $args the menu args.
	 *
	 * @return string;
	 */
	public function add_fallback_last_menu_items( $menu, $args ) {
		if ( $args['menu_id'] !== 'nv-primary-navigation' ) {
			return $menu;
		}

		$items = $this->get_last_menu_items_markup( '' );

		$menu = str_replace( '</ul>', $items . '</ul>', $menu );

		return $menu;
	}
}
