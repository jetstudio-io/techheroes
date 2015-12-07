            <div class="grid_6 alpha">	
									
						<div class="post">
				
								<div class="screenshot">
				
										<div class="screenimg">

											<?php if ( get_option('woo_resize') ) { woo_get_image('image','330','190','url'); } else { ?>
                                            
                                            <a href="<?php echo get_post_meta($post->ID, "url", $single = true); ?>" target="_blank"  title="View <?php the_title(); ?>"><img src="<?php echo get_post_meta($post->ID, "image", $single = true); ?>" alt="<?php the_title(); ?>" /></a>                                            

											<?php } ?>																	
					
										</div><!-- /screenimg -->
				
								</div><!-- /screenshot-->
					
						</div><!-- /post -->
            
          </div><!-- /grid_6 omega -->
            
          <div class="grid_6 omega">	
									
						<div class="post">
            
            				<h2><?php the_title(); ?></h2>
            
            				<p class="date"><strong><?php _e('Submitted on',woothemes); ?>:</strong> <?php the_time('d M y'); ?></p>
            
            				<p><strong><?php _e('Website Address',woothemes); ?>:</strong> <a title="<?php the_title(); ?>" href="<?php echo get_post_meta($post->ID, "url", $single = true); ?>" target="_blank" rel="bookmark"><?php echo get_post_meta($post->ID, "url", $single = true); ?></a>	</p>
            
            				<p><strong><?php _e('Category',woothemes); ?>:</strong> <?php the_category(','); ?></p>
            
            				<p><strong><?php _e('Website Rating',woothemes); ?>:</strong> <?php if(function_exists('the_ratings')) { the_ratings(); } ?></p>
					
						</div><!-- /post -->
            
           </div><!-- /grid_6 omega -->

            <div style="clear:both;height:15px;"></div>
            
            <div>
            	<h3><?php _e("Author's Description",woothemes); ?>:</h3>
                <?php the_content(); ?>
            </div>