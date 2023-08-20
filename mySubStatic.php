<?php
	/**
		* Plugin 	mySubStatic
		* @author	Cyrille G.  @ re7net.com
		* pages statique à deux niveaux
	**/
	class mySubStatic extends plxPlugin {
        const BEGIN_CODE = '<?php' . PHP_EOL;
        const END_CODE = PHP_EOL . '?>';
		public $subs;		
		
		/**
			* Constructeur de la classe
			*
			* @param	default_lang	langue par défaut
			* @return	stdio
			* @author	Stephane F
		**/
		public function __construct($default_lang) {
			
			# appel du constructeur de la classe plxPlugin (obligatoire)
			parent::__construct($default_lang);	

			# droits pour accèder à la page config.php du plugin
			$this->setConfigProfil(PROFIL_ADMIN);
			
			# déclaration des hooks
			$this->addHook('IndexBegin','IndexBegin');
			$this->addHook('plxShowStaticListBegin','plxShowStaticListBegin');
			$this->addHook('ThemeEndBody','ThemeEndBody');
			$this->addHook('SitemapEnd','SitemapEnd');
			
		}
		# filtrage des statiques avec un prefixe numerique sur 3 chiffre correspondant au un group de page statique
		public function IndexBegin() {
			$plxShow  = plxShow::getInstance();
			$plxMotor = plxMotor::getInstance();			
			$group_active = '';
			$format = '				';
			$format .=$this->getParam('format')=='' ? '<li class="#static_class #static_status" id="#static_id"><a href="#static_url" title="#static_name">#static_name</a></li>' : $this->getParam('format');
			$format .=PHP_EOL;
			$format_group = '			';
			$format_group.=$this->getParam('format_group')=='' ? '<span class="#group_class #group_status">#group_name</span>' : $this->getParam('format_group');
			$format_group.=PHP_EOL;
			
			foreach ($plxShow->plxMotor->aStats as $k => $v) {
				if($v['group'] !=''  and $v['group'] !='home' and array_key_exists(trim(substr($v['group'],0,3)),$plxShow->plxMotor->aStats)  ) {
					if ($group_active == ''  and $plxShow->staticId() == intval($k) and $v['group'] != '') 	$group_active = $v['group'];	
					$substat =strtr($format_group, [
						'#group_id'		=> 'static-group-' . plxUtils::urlify($k),
						'#group_class'	=> 'static group',
						'#group_status'	=> ($group_active == $k) ? 'active' : 'noactive',
						'#group_name'	=> substr($v['group'],3),
					]);
					
					#maj $url 
					if ($v['url'][0] == '?') # url interne commençant par ?
					{$url = $plxShow->plxMotor->urlRewrite($v['url']);}
					elseif (plxUtils::checkSite($v['url'], false)) # url externe en http ou autre
					{$url = $v['url'];}
					else # url page statique
					{	$url = $plxShow->plxMotor->urlRewrite('?static' . intval($k) . '/' . $v['url']);}
						
					$li=  strtr($format, [
						'#static_id'		=> 'static-' . intval($k),
						'#static_class'		=> 'static menu',
						'#static_name'		=> plxUtils::strCheck($v['name']),
						'#static_status'	=> ($plxShow->staticId() == intval($k)) ? 'active' : 'noactive',
						'#static_url'		=> $url,
					]);
					$mySubstats[$plxShow->plxMotor->aStats[substr($v['group'],0,3)]['group']][$substat][]= $li;
				}
			}
			$this->subs = $mySubstats;
		}
		
		# modification de la fonction staticList();
		public function plxShowStaticListBegin() {		
			echo self::BEGIN_CODE;
			?>
				include('<?= PLX_ROOT.'plugins/'.basename(__DIR__)?>/staticList.php');
				return true;
			<?php
            echo self::END_CODE;
		}
		
		# reinjection des sous statiques
		public function ThemeEndBody() {
			foreach($this->subs as $sub => $li) {
				$html='';
				$name=$sub;
				if(is_array($li)) {
					foreach($li as $k => $val) {
						$html .= $k.'			<ul class="sub-menu static-'.$sub.'">'.PHP_EOL;				
						if(is_array($val)) {
							foreach($val as $sub => $v) {
								$html .= $v;
							}
						}
					$html .='			</ul>'.PHP_EOL;
					}
				}			  
				echo self::BEGIN_CODE;
				?>
				$output = str_replace('</li><!-- <?= $name ?> -->', ob_get_clean().PHP_EOL.'<?= $html ?>		</li>', $output);
				$output = str_replace('home		', ob_get_clean().'', $output);/* ?? d'où vient cette chaine ? */
				<?php
				echo self::END_CODE;		
			}
		}
		
		#nettoyage sitemap.php 
		public function SitemapEnd() {			  
			echo self::BEGIN_CODE;
			?>
				$output = str_replace('home		', ob_get_clean().'', $output);/* ?? d'où vient cette chaine ? */
			<?php
			echo self::END_CODE;		
		}
	}
