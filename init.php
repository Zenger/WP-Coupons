<?php
class Zenger_Coupon
{

	static $list = "";
	public static function init()
	{
		

		if (isset($_POST['zcp_action']))
		{
			if (method_exists('Zenger_Coupon', $_POST['zcp_action']))
			{
				call_user_func('Zenger_Coupon::' . $_POST['zcp_action'] );
			}
		}

		add_action('admin_menu', array('Zenger_Coupon', 'add_page'));
	}

	public static function add_page()
	{
		add_menu_page( 'Coupon', 'Coupons', 'manage_options', 'zenger-coupon',array('Zenger_Coupon', 'render_page'),  get_template_directory_uri() . '/images/coupon-icon.png');
	}

	public static function render_page()
	{
		global $wpdb;

		$coupons = $wpdb->get_results( "SELECT ID, coupon_code, is_claimed, expires, source FROM ". $wpdb->prefix . "coupons ORDER BY ID DESC"  );
		?>

		<br />
			<p>
				<a href="<?php echo site_url(); ?>wp-login.php?action=register&secret=06466bfef6e7e40e52d572902df6757d"><?php echo site_url(); ?>/wp-login.php?action=register&secret=06466bfef6e7e40e52d572902df6757d</a>
			</p>
		<br />
		<table class="wp-list-table widefat fixed pages">
			<thead>
				<tr>
					<th>ID</th>
					<th>Code</th>
					<th>Source</th>
					<th>Remove</th>
				</tr>
			</thead>

			<tbody>
				<?php if (empty($coupons)) { ?>
					<tr><th colspan="4"> <p style='text-align:center;'>No coupons found</p> </th></tr>
				<?php } else { ?>

					<?php foreach($coupons as $coupon) { ?>
						<tr>
							<th><?php echo $coupon->ID; ?></th>
							<th><?php echo $coupon->coupon_code; ?></th>
							<th><?php echo $coupon->source; ?></th>
							<th>
								<form method="POST" action="">
									<input type="hidden" name="zcp_action" value="remove_coupon">
									<input type="hidden" name="zcp_id" value="<?php echo $coupon->ID; ?>">
									<input type="submit" class="button button-primary" value="- Remove Coupon">
								</form>
							</th>
						</tr>
					<?php } ?>

				<?php } ?>
			</tbody>
		</table>
		<br />

		<form method="POST" action="">
			<input type="hidden" name="zcp_action" value="generate_coupon">
			<input type="text" name="zcp_number" value="1">
			<input type="text" name="zcp_source" value="" placeholder="source">
			<input type="submit" class="button button-primary" value="+ Add Coupon">
		</form>

		<?php  echo ZNotice::get();  ?>
		<p>&nbsp;</p>
		<hr>
		
		<p>Generate List</p>
		<form action="" method="POST">
			<input type="hidden" name='zcp_action' value="generate_coupon_list" />
			<input type="text" name="zcp_source" placeholder="Source" />
			<input type="submit" class="button button-primary" value="Generate">
		</form>
		<br>
		
		

			<textarea class="widefat" name="" id="" cols="30" rows="10"><?php echo self::$list; ?></textarea>
		

		<?php
	}
	public static function generate_coupon_list()
	{
		global $wpdb;
		$source = esc_attr($_POST['zcp_source']);
		$coupons = $wpdb->get_results( $wpdb->prepare("SELECT coupon_code FROM ". $wpdb->prefix. "coupons WHERE source = %s ", $source) );
		
		
		foreach($coupons as $coupon)
		{
			$COUPONS .= $coupon->coupon_code . "\n";
		}
		
		$COUPONS = trim($COUPONS);

		self::$list = $COUPONS;
	}
	public static function generate_coupon()
	{
		ZNotice::set_markup("<div class='updated alert alert-%s'>%s</div>");

		$coupons = intval($_POST['zcp_number']);
		if ( $coupons == 0)
		{
			ZNotice::error("Number of coupons must not be 0");
		}

		$i = 0;
		if ($coupons > 1000)
		{
			ZNotice::error("Woah, too many coupons, ask less then 1000");
		}
		else
		{
			while ($i < $coupons)
			{
				global $wpdb;
				$secret_key = strtoupper( substr( base64_encode( sha1(md5(rand()) . NONCE_SALT . sha1(mt_rand())) ), 0, mt_rand(5, 10) ) );
				$source = esc_attr( $_POST['zcp_source'] );
				$wpdb->query( $wpdb->prepare("INSERT INTO ".$wpdb->prefix."coupons (`coupon_code`, `expires`, `is_claimed`, `source`) VALUES (%s, NOW() , 0, %s) " , $secret_key, $source) );
				$i++;
			}
			ZNotice::success(sprintf("%s coupon codes were generated", $coupons));
		}
	}

	public static function remove_coupon()
	{
		$id = intval($_POST['zcp_id']);
		if ($id == 0)
		{
			ZNotice::error("Invalid request");
		}
		else
		{
			global $wpdb;
			$query = $wpdb->query( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . "coupons WHERE id= %d" , $id) );
			if (!$query)
			{
				ZNotice::error("Something went wrong with the query");
			}
			else
			{
				ZNotice::success( sprintf("The coupon with the ID of %d was deleted", $id ) );
			}
		}
	}

	public static function check_coupon( $coupon )
	{
		global $wpdb;

		$result = $wpdb->query( $wpdb->prepare("SELECT ID, is_claimed FROM ". $wpdb->prefix . "coupons WHERE coupon_code = %s ", $coupon) );

		if ( false === $result || $result == 0 )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	public static function claim_coupon( $coupon, $user_id = false)
	{
		global $wpdb;

		$source = $wpdb->get_var( $wpdb->prepare("SELECT source FROM ". $wpdb->prefix . "coupons WHERE coupon_code =%s ", $coupon) );
		$query = $wpdb->query( $wpdb->prepare("DELETE FROM ". $wpdb->prefix . "coupons WHERE coupon_code = %s" , $coupon) );

		if (false != $user_id)
		{
			update_usermeta( $user_id, 'coupon_source', $source );
		}

		if ($query)
		{
			return true;
		}
		return false;
	}
}

Zenger_Coupon::init();

