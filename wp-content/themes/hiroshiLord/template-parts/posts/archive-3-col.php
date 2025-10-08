<?php

/**
 * Posts archive 3 column.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.18.0
 */

if (have_posts()) : ?>
	<div id="post-list">
		<?php
		$ids = array();
		while (have_posts()) : the_post();
			array_push($ids, get_the_ID());
		endwhile; // end of the loop.
		$ids = implode(',', $ids);
		?>

		<?php
		echo flatsome_apply_shortcode('blog_posts', array(
			'style'     => 'default',
			'columns'     => '3',
			'col_spacing' => 'large',
			'title_size'     => 'xlarge',
			'show_date'     => 'text',
			"show_category" => "text",
			'comments'     => 'false',

			"text_bg" => "rgb(247, 247, 247)",
			'text_padding'     => "1rem 1.6rem 1rem 1.6rem",
			'class'     => 'obelix-blog-list--nine',
			"image_height" => "87%",
			"image_size" => "large",
			'text_align'  => get_theme_mod('blog_posts_title_align', 'center'),
			'type'        => get_theme_mod('blog_style_type', 'masonry'),
			'depth'       => get_theme_mod('blog_posts_depth', 0),
			'depth_hover' => get_theme_mod('blog_posts_depth_hover', 0),
			'ids'         => $ids
		));
		?>

		<?php flatsome_posts_pagination(); ?>
	</div>
<?php else : ?>

	<?php get_template_part('template-parts/posts/content', 'none'); ?>

<?php endif; ?>