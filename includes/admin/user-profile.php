<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function affwp_user_profile_fields( $user ) {
  
	if( ! current_user_can( 'manage_affiliates' ) ) {
		return;
	}

	if( isset( $_GET['register_affiliate'] ) && 1 == absint( $_GET['register_affiliate'] ) ) {
		$affiliate_id = affwp_add_affiliate( array( 'user_id' => $user->ID ) );
	} else {
		$affiliate_id = affwp_get_affiliate_id( $user->ID );
	}

	$affiliate = 0 < absint( $affiliate_id ) ? affwp_get_affiliate( $affiliate_id ) : false;
	?>
	<h3><?php esc_attr_e( 'AffiliateWP', 'affiliate-wp' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label><?php esc_attr_e( 'Affiliate Registration Status', 'affiliate-wp' ); ?></label></th>
			<td>
				<?php if( $affiliate ):
					switch( $affiliate->status ) {
						case 'active':
							echo '<span style="color: green; font-weight: bold;">' . esc_attr( 'Active', 'affiliate-wp' ) . '</span>';
							break;
						case 'inactive':
							echo '<span>' . esc_attr( 'Inactive', 'affiliate-wp' ) . '</span>';
							break;
						case 'pending':
							echo '<span style="color: silver;">' . esc_attr( 'Pending', 'affiliate-wp' ) . '</span>';
							break;
						case 'rejected':
							echo '<span style="color: red; font-weight: bold;">' . esc_attr( 'Rejected', 'affiliate-wp' ) . '</span>';
							break;
					}
				else: ?>
				<span style="color: red;"><?php esc_attr_e( 'Not Registered', 'affiliate-wp' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<th><label><?php esc_attr_e( 'Affiliate Actions', 'affiliate-wp' ); ?></label></th>
			<td>
				<?php if( $affiliate ):
					echo '<a href="' . esc_url( add_query_arg( array( 'affwp_notice' => false, 'affiliate_id' => $affiliate->affiliate_id, 'action' => 'view_affiliate' ), admin_url( 'admin.php?page=affiliate-wp-affiliates' ) ) ) . '" class="button">' . __( 'Reports', 'affiliate-wp' ) . '</a>';
					echo ' ';
					echo '<a href="' . esc_url( add_query_arg( array( 'affwp_notice' => false, 'action' => 'edit_affiliate', 'affiliate_id' => $affiliate->affiliate_id ), admin_url( 'admin.php?page=affiliate-wp-affiliates' ) ) ) . '" class="button">' . __( 'Edit', 'affiliate-wp' ) . '</a>';
				else: ?>
					<a href="<?php echo add_query_arg( array( 'user_id' => $user->ID, 'register_affiliate' => 1 ), admin_url( 'user-edit.php' ) ); ?>" class="button"><?php esc_attr_e( 'Register', 'affiliate-wp' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'affwp_user_profile_fields' );
add_action( 'edit_user_profile', 'affwp_user_profile_fields' );