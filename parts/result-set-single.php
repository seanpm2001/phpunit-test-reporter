<?php
use PTR\Display;

echo Display::get_display_css();

foreach ( $revisions as $revision ) :

  $rev_id = (int) ltrim( $revision->post_name, 'r' );
?>

<table>
	<thead>
    <tr>
      <td colspan="3">
        <a
            href="<?php echo esc_url( sprintf( 'https://core.trac.wordpress.org/changeset/%d', $rev_id ) ); ?>"
            title="<?php echo esc_attr( apply_filters( 'the_title', $revision->post_title ) ); ?>">
          r<?php echo $rev_id; ?>
        </a>
      </td>
    </tr>
		<tr>
			<th style="width:100px">Status</th>
			<th style="width:150px">PHP Version</th>
			<th>Database Version</th>
		</tr>
	</thead>
	<tbody>

			<?php
			$query_args = array(
				'posts_per_page' => $posts_per_page,
				'post_type'      => 'result',
				'post_parent'    => $revision->ID,
				'orderby'        => [ 'author' => 'ASC', 'php_version_clause' => 'ASC' ],
				'meta_query'     => array(
					'relation' => 'OR',
					'php_version_clause' =>	array(
						'key'     => 'php_version',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'php_version',
						'compare' => 'NOT EXISTS',
					),
					'env_name_clause' => array(
						'key'     => 'environment_name',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'environment_name',
						'compare' => 'NOT EXISTS',
					)
				),
			);
			$report_query = new WP_Query( $query_args );
			if ( ! empty( $report_query->posts ) ) :

          $prev_author = null;

				foreach ( $report_query->posts as $report ) :
					$status       = 'Errored';
					$status_title = 'No results found for test.';
					$results      = get_post_meta( $report->ID, 'results', true );
					if ( isset( $results['failures'] ) && ! empty( $results['tests'] ) ) {
						$status       = 0 === (int) $results['failures'] && 0 === (int) $results['errors'] ? 'Passed' : 'Failed';
						$status_title = (int) $results['tests'] . ' tests, ' . (int) $results['failures'] . ' failed, ' . (int) $results['errors'] . ' errors';
					}
					$host = 'Unknown';
					$user = get_user_by( 'id', $report->post_author );
					if ( $user ) {
						$host = '';
						if ( ! empty( $user->user_url ) ) {
							$host .= '<a target="_blank" rel="nofollow" href="' . esc_url( $user->user_url ) . '">';
						}
						$host .= get_avatar(
							$user->ID,
							18,
							'',
							'',
							array(
								'extra_attr' => 'style="vertical-align: middle;margin-right:5px;"',
							)
						);
						if ( ! empty( $user->user_url ) ) {
							$host .= '</a>';
						}
						if ( ! empty( $user->user_url ) ) {
							$host .= '<a target="_blank" rel="nofollow" href="' . esc_url( $user->user_url ) . '">';
						}
						$host .= Display::get_display_environment_name( $report->ID );

						if ( ! empty( $user->user_url ) ) {
							$host .= '</a>';
						}
					}
					?>
        <?php if ( $prev_author !== $host ): ?>
          <tr>
            <td colspan="3">
              <?php echo wp_kses_post( $host ); ?>
            </td>
          </tr>
        <?php endif; ?>
				<tr>
					<td style="text-align:center"><a href="<?php echo esc_url( get_permalink( $report->ID ) ); ?>" title="<?php echo esc_attr( $status_title ); ?>" class="<?php echo esc_attr( 'ptr-status-badge ptr-status-badge-' . strtolower( $status ) ); ?>"><?php echo esc_html( $status ); ?></a></td>
					<td style="text-align:center"><?php echo esc_html( Display::get_display_php_version( $report->ID ) ); ?></td>
					<td><?php echo esc_html( Display::get_display_mysql_version( $report->ID ) ); ?></td>
				</tr>
					<?php
			    $prev_author = $host;
				endforeach;
			else :
				?>
				<tr>
					<td></td>
					<td colspan="3">
						No reports for changeset.
					</td>
				</tr>
			<?php endif; ?>
		<?php endforeach; ?>
	</tbody>
</table>
