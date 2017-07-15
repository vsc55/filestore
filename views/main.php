<?php
$first = true;
$tabs = '';
$tabcontent = '';
foreach ($drivers as $driver) {
  $class = $first?"active":"";
  $tabs .= '<li role="presentation" class="'.$class.'"><a href="#'.$driver.'" data-toggle="tab">'.$driver.'</a></li>';
  $class = $first?"in active":"";
  $tabcontent .= '<div id="'.$driver.'" class="tab-pane fade '.$class.'">';
  $tabcontent .= $fs->getDisplay($driver);
  $tabcontent .= '</div>';
  $first = false;
}
$first = true;
$settingtabcontent = '';
$settingstabs = '';
foreach ($drivers as $driver) {
    $content = $fs->getSettingDisplay($driver);
    if(!empty($content)){
      $class = $first?"active":"";
      $settingstabs .= '<li role="presentation" class="'.$class.'"><a href="#settings'.$driver.'" data-toggle="tab">'.$driver.'</a></li>';
      $class = $first?"in active":"";
      $settingtabcontent .= '<div id="settings'.$driver.'" class="tab-pane fade '.$class.'">';
      $settingtabcontent .= $content;
      $settingtabcontent .= '</div>';
      $first = false;
    }
}
?>
<ul class="nav nav-tabs">
  <?php echo $tabs ?>
  <li role="presentation" class="<?php echo empty($settingtabcontent)?'hidden':'';?>"><a href="#Settings" data-toggle="tab"><?php echo _("Settings")?></a></li>
</ul>
<div class="tab-content">
  <?php echo $tabcontent;?>
  <div id="Settings" class="tab-pane fade">
    <ul class="nav nav-tabs">
      <?php echo $settingstabs ?>
    </ul>
    <div class="tab-content">
      <?php echo $settingtabcontent ?>
    </div>
    <?php echo empty($settingtabcontent)?_("No modules support settings"):''?>
  </div>
</div>
