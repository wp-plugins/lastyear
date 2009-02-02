<?php
/*
Plugin Name: LastYear
Plugin URI: http://www.guiadopc.com.br/plugins/lastyear/
Description: Lists posts made today, on previous years.
Author: Mario Zunino
Version: 0.1
Author URI: http://twitter.com/mariogw
*/

/*
Copyright (c) 2009 Mario Zunino.

This software is provided 'as-is', without any express or implied
warranty. In no event will the authors be held liable for any damages
arising from the use of this software.

Permission is granted to anyone to use this software for any purpose,
including commercial applications, and to alter it and redistribute it
freely, subject to the following restrictions:

    1. The origin of this software must not be misrepresented; you must not
    claim that you wrote the original software. If you use this software
    in a product, an acknowledgment in the product documentation would be
    appreciated but is not required.

    2. Altered source versions must be plainly marked as such, and must not be
    misrepresented as being the original software.

    3. This notice may not be removed or altered from any source
    distribution.
*/

//Plugin version, control only
$lyVersion = '0.1';

//Registers the options page
function lyAddOptionsPage()
{
	if(function_exists('add_options_page'))
	{
		add_options_page('LastYear Options', 'LastYear', 8, __FILE__, 'lyOptionsPage');
	}
}

//Shows the options page, updates the configuration, etc.
function lyOptionsPage()
{

	global $lyVersion;
	$lySetDefauls = $_POST['setDefaults'];
	$lyUpdateOptions = $_POST['updateOptions'];
	
	if(isset($lySetDefauls)) 
	{
		lySetDefaults();
			
		echo 
		'
			<div id="message" class="updated fade">
				<p>
					<strong>Defaults loaded!</strong>
				</p>
			</div>
		';

		lyLoadOptionsPage();
		return;
	}
	elseif(isset($lyUpdateOptions)) 
	{
		if(!is_numeric($_POST["lyPostCount"])) 
		{
			echo 
			'
				<div id="message" class="updated fade">
					<p>
						<strong>"Posts per page" MUST be an integer GREATER than 0.</strong>
					</p>
				</div>
			';
			
			lyLoadOptionsPage();
			return;
		}
		elseif($_POST["lyPostCount"] <= 0)
		{
			echo 
			'
				<div id="message" class="updated fade">
					<p>
						<strong>"Posts per page" MUST be an integer GREATER than 0.</strong>
					</p>
				</div>
			';
			
			lyLoadOptionsPage();
			return;
		}
		else
		{
			$lyPostCount = $_POST["lyPostCount"];
			$lyHeader = $_POST["lyHeader"];
			$lyNoPosts = $_POST["lyNoPosts"];
			
			if(isset($_POST["lyShowYear"]))
			{
				$lyShowYear = true;
			}
			else
			{
				$lyShowYear = false;
			}
			
			update_option('lyPostCount', (int)$lyPostCount);
			update_option('lyHeader', (string)$lyHeader);
			update_option('lyShowYear', (bool)$lyShowYear);
			update_option('lyNoPosts', (string)$lyNoPosts);
			
			echo 
			'
				<div id="message" class="updated fade">
					<p>
						<strong>Options updated!</strong>
					</p>
				</div>
			';
			
			lyLoadOptionsPage();
			return;
		}
	}
	else
	{
		lyLoadOptionsPage();
	}
}

//Options page code
function lyLoadOptionsPage()
{
	$lyShowYear = get_option('lyShowYear');
	if($lyShowYear == true)
	{
		$lyShowYear = 'checked="checked"';
	}
	else
	{
		$lyShowYear = '';
	}
	
	echo
	'
		<div class="wrap">
			
			<h2 style="margin-bottom: 0;">LastYear Plugin by <a href="http://twitter.com/mariogw">Mario Zunino</a></h2>

			<form method="post" action="'.$_SERVER["REQUEST_URL"].'"

				<h3>Options:</h3>
				
				<p>
					<label>Posts per page: <br /> <input name="lyPostCount" type="text" value="'.get_option('lyPostCount').'" /></label><br />
					<label>Pre-posts header: <br /> <input name="lyHeader" type="text" value="'.get_option('lyHeader').'" /></label><br />
					<label>Show post year? <br /><input name="lyShowYear" type="checkbox" value="lyShowYear"'.$lyShowYear.' /></label><br />				
					<label>No posts message: <br /> <input name="lyNoPosts" type="text" value="'.get_option('lyNoPosts').'" /></label><br />
				</p>
				
				<input type="hidden" name="updateOptions" id="updateOptions" value="true" />

				<p class="submit">
					<input type="submit" name="updateOptions" value="Update Options" />
					<input type="submit" name="setDefaults" value="Load Defaults" />
				</p>
			
			</form>
			<p>
			Special thanks to <a href="http://twitter.com/paulohiga">Paulo Seikishi Higa</a> who made this beautiful options page HTML code. I really hate HTML.
			<p/>
		</div>
	';
}

//Retrieve the posts from DB
function lyGetPosts() 
{
	global $wpdb, $post;

	$wpTable = $wpdb->prefix.'posts';
	
	$lyPostCount = get_option('lyPostCount');
	$lyHeader = get_option('lyHeader');
	$lyShowYear = get_option('lyShowYear');
	$lyNoPosts = get_option('lyNoPosts');

	if(!is_single())
	{
		$lyCurrentDay = date('d');
		$lyCurrentMonth = date('m');
		$lyCurrentYear = date('Y');
		
		$lyLastYearPosts = $wpdb->get_results
		("
			SELECT ID, post_title, DATE_FORMAT(post_date, '%Y') AS post_year 
			FROM $wpTable
			WHERE post_date < NOW()
			AND post_type = 'post' 
			AND post_password = ''
			AND post_status = 'publish'
			AND DATE_FORMAT(post_date, '%d') = $lyCurrentDay
			AND DATE_FORMAT(post_date, '%m') = $lyCurrentMonth 
			AND DATE_FORMAT(post_date, '%Y') != $lyCurrentYear
			ORDER by post_date DESC
			LIMIT $lyPostCount
		");
		
		if(empty($lyLastYearPosts))
		{
			$lyNoPosts = $lyHeader.$lyNoPosts;
			return $lyNoPosts;
		}

		$lyPosts = $lyHeader.'<ul>';

		foreach($lyLastYearPosts as $lyPost) 
		{
			$lyPosts .= '<li>';
			$lyPosts .= '<a href="'.get_permalink($lyPost->ID).'">'.$lyPost->post_title.'</a>';
			if($lyShowYear == true)
			{
				$lyPosts .= ' ('.$lyPost->post_year.')';
			}
			$lyPosts .= '</li>';
		}

		$lyPosts .= '</ul>';

		return $lyPosts;
	}

	$lyCurrentDay = date('d', strtotime($post->post_date));
	$lyCurrentMonth = date('m', strtotime($post->post_date));
	$lyCurrentYear = date('Y', strtotime($post->post_date));
	$lyCurrentPost = $post->ID;

	$lyLastYearPosts = $wpdb->get_results
	("
		SELECT ID, post_title, DATE_FORMAT(post_date, '%Y') AS post_year 
		FROM $wpTable
		WHERE post_date < NOW()
		AND ID != $lyCurrentPost
		AND post_type = 'post' 
		AND post_password = ''
		AND post_status = 'publish'
		AND DATE_FORMAT(post_date, '%d') = $lyCurrentDay
		AND DATE_FORMAT(post_date, '%m') = $lyCurrentMonth 
		AND DATE_FORMAT(post_date, '%Y') != $lyCurrentYear
		ORDER by post_date DESC
		LIMIT $lyPostCount
	");
	
	if(empty($lyLastYearPosts))
	{
		$lyNoPosts = $lyHeader.$lyNoPosts;
		return $lyNoPosts;
	}

	$lyPosts = $lyHeader.'<ul>';

	foreach($lyLastYearPosts as $lyPost) 
	{
		$lyPosts .= '<li>';
		$lyPosts .= '<a href="'.get_permalink($lyPost->ID).'">'.$lyPost->post_title.'</a>';
		if($lyShowYear == true)
		{
			$lyPosts .= ' ('.$lyPost->post_year.')';
		}
		$lyPosts .= '</li>';
	}

	$lyPosts .= '</ul>';

	return $lyPosts;
}

//Shows the post, this is the "template tag"
function lyShowPosts()
{
	$lyPosts = lyGetPosts();
	
	echo "$lyPosts";
}

//Sets the default options
function lySetDefaults()
{
	if(get_option('lyPostCount'))
	{
		update_option('lyPostCount', '10');
	}
	else
	{
		add_option('lyPostCount', '10');
	}
	
	if(get_option('lyHeader'))
	{
		update_option('lyHeader', '<h3>Today, last year..</h3>');
	}
	else
	{
		add_option('lyHeader', '<h3>Today, last year..</h3>');
	}
	
	if(get_option('lyShowYear'))
	{
		update_option('lyShowYear', true);
	}
	else
	{
		add_option('lyShowYear', true);
	}
	
	if(get_option('lyNoPosts'))
	{
		update_option('lyNoPosts', '...there were no posts.');
	}
	else
	{
		add_option('lyNoPosts', '...there were no posts.');
	}
}

### "INSTALL" RELATED ###
//Sets default config
add_option('lyPostCount', '10');
add_option('lyHeader', '<h3>Today, last year...</h3>');
add_option('lyShowYear', true);
add_option('lyNoPosts', '...there were no posts.');

//Registers the menu options page
add_action('admin_menu', 'lyAddOptionsPage');
?>