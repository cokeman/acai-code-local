<?

$otros_contenidos = CocoDB::get("otros_contenidos", "visible_en_el_menu=1", "siblingOrder DESC", null, ["ignoreSchema" => true]);
$config_footer 	= 	array(
		                'pie_de_pagina'				=>	t($configuracionRecord,"pie_de_pagina"),
		                'configuracionRecord'		=>	@$configuracionRecord,
		                'configuracionTienda'		=>	@$configuracionTienda,
						'thisrecord'				=>	@$apartado,
		                'index'						=>	@$index,
						'otros_contenidos'					=>	$otros_contenidos,
						'apartadoCesta'				=>	@$apartadoCesta
					);

if (@$customCode) echo modulo('custom-footer',$config_footer); else echo tpl('pie',$config_footer);	
