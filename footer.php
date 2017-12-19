		</div> <?php //sitewrapper ?>

			<footer class="site-footer" id="footer">
				<div class="row align-middle">
					<div class="column">
						<?php
						$zm      = Zonemaster::get_instance();
						$version = $zm->get_zm_version();
						echo esc_html( $version );
						?>
					</div>
					<div class="columns shrink">
					<?php
					$proxynonce = wp_create_nonce( $version );
					// Basic nonce to use when trying to verify javascript
					echo '<div class="hide" id="proxynonce">' . $proxynonce . '</div>';
					?>

				</div>

				</div>
			</footer>
		<?php wp_footer(); ?>

	</body>
</html>
