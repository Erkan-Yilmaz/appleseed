<!DOCTYPE html>
<html lang="en">

<head>
	
  	<?php $zApp->Components->Go ( 'system', 'head', 'head' ); ?>

</head>

<body id="appleseed">
	
  	<?php $zApp->Components->Go ( "system" ); ?>

	<div class="clear"></div>

	<!-- Header -->
	<header id="appleseed-header">
 		<?php $zApp->Components->Go ( "header" ); ?>
 	</header>

	<div id="appleseed-logo"></div>
	
	<div id="appleseed-container" class="container_16">
	
    	<div id="appleseed-admin" class="container_16">
	       	<div id="appleseed-admin-menu" class="container_16">
	       		<nav id="admin-tabs" class="grid_9 push_4">
	       			<ul>
	       				<li class="selected"><a href="/example/">Example</a></li>
		       		</ul>
		       	</nav>
		       	<div id="admin-search" class="grid_3 push_4">
					<?php $zApp->Components->Go ( "search", "search", "local" ); ?>
				</div>
			</div>
       
			<div id="appleseed-admin-main" class="grid_16">
				<div id="appleseed-admin-main-menu" class="grid_4 alpha">
					<section id="admin-main-menu">
						<h1>Example</h1>
					</section>
				</div>
				<div id="appleseed-admin-content" class="grid_12 omega">
		 			<?php
				  	/*
				  	 * @tutorial Parameters:  
				  	 * @tutorial string pComponent, string pController, string pView, string pTask, array pData
				  	 * 
				  	 * @tutorial You can shorten a component call by putting pData earlier than 
				  	 * @tutorial it should be.  For instance:
				  	 * 
				  	 * @tutorial ->Go ( "example", array ( "Key" => "Value" ) );
				  	 * 
				  	 * @tutorial The system will detect the array being passed, and call the example 
				  	 * @tutorial component with the default controller, view, and task.
				  	 */
				  	?>
					<?php $zApp->Components->Go ( "example", "example", "example" ); ?>
				</div>
			</div>
		</div>
        
    </div>

	<div class="clear"></div>
    
    <footer id="appleseed-footer" class="container_16">
 		<?php $zApp->Components->Go ( "footer" ); ?>
 	</footer>

</body>
</html>
