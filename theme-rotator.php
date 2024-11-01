<?php
/*
Plugin Name: Theme Rotator
Version: 0.6
Plugin URI: http://blog.gr80.net
Description: Theme Rotator
Author: Shady A.Sharaf <shady@gr80.net>
Author URI: http://blog.gr80.net

Copyright 2010  (email:shady@gr80.net )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class gr80_theme_rotator
{
	
	function __construct()
	{
		$this->db =& $wpdb;
		$this->rules = get_option('gr80_theme_rotator') OR array();
		 
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('setup_theme', array($this, 'setup_theme'));
	}
	
	function add($time, $theme)
	{
		$this->rules[ strtotime($time) ] = $theme;
		ksort($this->rules);
		update_option('gr80_theme_rotator', $this->rules);
	}
	
	function admin_menu()
	{
		add_submenu_page('themes.php', _('Theme rotator'), _('Theme rotator'), 'edit_options', 'gr80_theme_rotator', array($this, 'page') );
	}
	
	function get_themes()
	{
		$themes = get_themes();
	
		$mod_themes = array();
		foreach ($themes as $theme) {
			$mod_themes[ "{$theme['Template']}|{$theme['Stylesheet']}" ] = $theme;
		}
		return $mod_themes;
	}
	
	function page()
	{
		$themes = $this->get_themes();
		if($_POST){
			$post_theme = $_POST['theme'];
			$theme = reset(array_filter($themes, create_function('$theme', '
				return $theme["Template"] ."|". $theme["Stylesheet"] == $_POST["theme"];
			')));
			$this->add($_POST['activate_time'], $theme);
		}elseif(isset($_GET['delete'])){
			$time = $_GET['delete'];
			unset($this->rules[$time]);
			update_option('gr80_theme_rotator', $this->rules);
		}
		
		?>
		<div class="wrap">
			<?php screen_icon( 'edit-pages' ); ?>
			<h2><?php _e('Theme Rotator') ?></h2>
			<h3><?php _e('Assign Start dates for Themes to rotate automatically.')  ?></h3>
			
			<?php $current_theme = get_theme(get_current_theme()); ?>
			<p>Current theme: 
				<strong><?php echo $current_theme['Title']; ?></strong> by <?php echo $current_theme['Author'] ?>
			</p>
			
			<?php if (!empty($this->rules)): ?>
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e('Name') ?></th>
						<th><?php _e('Activation Time') ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
						
					<?php foreach ($this->rules as $time => $theme): ?>
					<tr>
						<td>
							<strong><?php echo $theme['Name'] ?></strong><br/>
							<?php echo $theme['Author'] ?>
						</td>
						<td>
							<?php echo date('d/m/Y h:ia', $time) ?>
						</td>
						<td>
							<a href="?page=gr80_theme_rotator&delete=<?php echo $time ?>"><?php _e('Delete') ?></a>
						</td>
					</tr>
					<?php endforeach ?>
				</tbody>
			</table>
			<?php else: ?>
			<p><?php _e('You do not yet have any themes scheduled, please set one schedule below.') ?></p>
			<?php endif ?>
			
			<h3><?php _e('Add new rule') ?></h3>
			<form action="?page=gr80_theme_rotator" method="post">
				<table class="form-table">
					<tbody>
						<tr class="even">
							<th scope="row">
								<label for="theme"><?php _e('Template') ?></label>
							</th>
							<td>
								<select name="theme" id="theme" class="postform">
									<?php foreach ($themes as $theme_id => $theme): ?>
										<option value="<?php echo $theme_id ?>">
											<?php echo "{$theme['Name']} [{$theme['Stylesheet']}]" ?>
										</option>
									<?php endforeach ?>
								</select>
							</td>
						</tr>
						<tr class="odd">
							<th scope="row">
								<label for="activate_time"><?php _e('Start') ?></label>
							</th>
							<td>
								<input type="text" name="activate_time" id="activate_time" class="datepick" />
								<label for="">
									ex: <strong><?php echo date('d-m-Y h:i a',strtotime('14-09-2010 04:00 am')) ?></strong>
								</label>
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<th scope="row"></th>
							<td>
								<input type="submit" value="<?php _e('Assign') ?>" />
							</td>
						</tr>
					</tfoot>
				</table>
				<input type="hidden" name="action" value="add" />
			</form>
			
			<h3><?php _e('Current Theme') ?></h3>
			
			
		</div>
		<?php
	}
	
	function setup_theme()
	{
		$current_theme = get_theme(get_current_theme());
		if( $current_theme == null ) return -1;
		
		$cr_template = $current_theme['Template']; $cr_stylesheet = $current_theme['Stylesheet'];
		
		$now = time();
		$schedules = array_filter(array_keys($this->rules), create_function('$time', '$now = '.$now.';
			return $now > $time;
		'));
		$next_schedule = reset($schedules);
		
		if(empty($next_schedule))
			return;
		
		$next_theme = $this->rules[$next_schedule];
		
		if ( $next_theme['Template'] == $cr_template && $next_theme['Stylesheet'] == $cr_stylesheet ) {
			return false;
		}else{
			switch_theme($next_theme['Template'],$next_theme['Stylesheet']);
			wp_mail(get_option('admin_email'), 'Theme rotator: '.$next_theme['Title'], 'Theme rotator has changed your theme to '.$next_theme['Title'].'based on your settings.', 'From: ');
		}
	}
	
}

$gr80_theme_rotator = new gr80_theme_rotator;