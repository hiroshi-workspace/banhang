<?php
/**
 * Portfolio single.
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.19.9
 */

get_template_part( 'template-parts/portfolio/portfolio-title', get_theme_mod( 'portfolio_title', '' ) );
?>
<div class="portfolio-top my-portfolio-hiroshi" >
	<div class="page-wrapper row row-collapse">
  	<div class="large-0 col col-divided" style="display:none">
  		<div class="portfolio-summary entry-summary sticky-sidebar">
  				<?php get_template_part('template-parts/portfolio/portfolio-summary'); ?>
  		</div>
  	</div>

  	<div id="portfolio-content" class="large-12 col"  role="main">
  		<div class="portfolio-inner">
  			<?php get_template_part('template-parts/portfolio/portfolio-content'); ?>
  		</div>
  	</div>
	</div>
</div>

<div class="portfolio-bottom" style="display:none">
	<?php get_template_part('template-parts/portfolio/portfolio-next-prev'); ?>
	<?php get_template_part('template-parts/portfolio/portfolio-related'); ?>
</div>
