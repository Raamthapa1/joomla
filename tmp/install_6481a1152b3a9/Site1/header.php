<?php
    $document = JFactory::getDocument();
?>
    <header class="u-clearfix u-header u-header" id="sec-4e50">
  <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
    <?php $logoInfo = getLogoInfo(array(
            'src' => "/images/default-logo.png",
            'href' => "https://nicepage.com",
        ), true); ?><a href="<?php echo $logoInfo['href']; ?>" class="u-image u-logo u-image-1">
      <img src="<?php echo $logoInfo['src']; ?>" class="u-logo-image u-logo-image-1">
    </a>
    <?php echo CoreStatements::position('hmenu', '', 1, 'hmenu'); ?>
  </div>
</header>
    