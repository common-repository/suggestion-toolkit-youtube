<?php
		foreach ( $r_posts as $post ) { ?>
            <?php ?>
            <div class="sggtool-cell sggtool-cell-cnt" data-title="<?php echo $post->title; ?>" data-date="<?php echo strtotime($post->date); ?>">
                <a class="sggtool-post-image youtube" href="<?php echo $post->url; ?>" title="<?php echo $post->title; ?>" onclick="sggtool_showVideo('<?php echo $post->ID; ?>'); return false;">
                	<div class="sggtool-image" style="background-image: url('<?php echo $post->image; ?>');"></div>
                	<i class="fa fa-youtube-play sggtool-youtube" aria-hidden="true"></i>
                </a>
                <div class="sggtool-post-info">
                    <a class="sggtool-post-title" href="<?php echo $post->url; ?>"  title="<?php echo $post->title; ?>" onclick="sggtool_showVideo('<?php echo $post->ID; ?>'); return false;"><?php echo wp_trim_words($post->title, $num_title_words, "...") ; ?></a>
		            <?php if ( $cfg['show_date'] == 1) : ?>
		            <span class="sggtool-post-date"><?php echo date( get_option('date_format'), strtotime($post->date) ); ?></span>
		            <?php endif; ?>
                </div>
            </div>
        <?php } 
        
?>
