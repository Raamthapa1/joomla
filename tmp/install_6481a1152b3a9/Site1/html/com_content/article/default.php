<?php
defined('_JEXEC') or die;

$text = $this->item->introtext;
if (property_exists($this->item, 'nicepage') && strpos($text, '<!--np_landing-->') !== false) {
    echo $text;
    return;
}

require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'functions.php';

$defaultLogo = getLogoInfo(array('src' => "/images/default-logo.png"));
$logoPath = $defaultLogo['src_path'];
$logoUrl = $defaultLogo['src'];
$logoWidth = '';
$logoHeight = '';
if ($logoPath) {
    $logoInfo = getimagesize($logoPath);
    $logoWidth = $logoInfo[0];
    $logoHeight = $logoInfo[1];
}

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

$document = JFactory::getDocument();

Core::load("Core_Content");

$component = new CoreContent($this, $this->params);

$article = $component->article('article', $this->item, $this->item->params, array('print' => $this->print));

$document->seoTags['og:type'] = array('tag' => 'meta', 'property' => 'og:type', 'content' => 'article');
$document->seoTags['og:title'] = array('tag' => 'meta', 'property' => 'og:title', 'content' => $article->title);

$app = JFactory::getApplication();
$themeParams = $app->getTemplate(true)->params;
if ($themeParams->get('jsonld', '0') == '1') {
    ob_start();

    $config = JFactory::getConfig();

    ?>
    <script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "Article",
    "articleBody": <?php echo json_encode(strip_tags($article->text($article->text))); ?>,
    "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "<?php echo JUri::getInstance()->toString(); ?>"
    },
    "headline": "<?php echo $article->title; ?>",
    "description": <?php echo json_encode(strip_tags($article->intro($article->intro))); ?>,
    <?php
        $image = $article->images['fulltext']['image'];
        if ($image) {
            $root = str_replace(DIRECTORY_SEPARATOR, '/', JPATH_ROOT);
            $imagesPath = $root . '/' . $image;
            $imageUrl = dirname(JURI::current()) . '/' . $image;
            $info = @getimagesize($imagesPath);

            $document->seoTags['og:image'] = array('tag' => 'meta', 'property' => 'og:image', 'content' => $imageUrl);
            $document->seoTags['og:secure_url'] = array('tag' => 'meta', 'property' => 'og:image:secure_url', 'content' => str_replace('http:', 'https:', $imageUrl));
            $document->seoTags['og:height'] = array('tag' => 'meta', 'property' => 'og:image:height', 'content' => @$info[1]);
            $document->seoTags['og:width'] = array('tag' => 'meta', 'property' => 'og:image:width', 'content' => @$info[0]);
            $alt = $article->images['fulltext']['alt'];
            if ($alt) {
                $document->seoTags['og:alt'] = array('tag' => 'meta', 'property' => 'og:image:alt', 'content' => $alt);
            }
            ?>
    "image": {
        "@type": "ImageObject",
        "url": "<?php echo $imageUrl; ?>",
        "height": <?php echo @$info[1]; ?>,
        "width": <?php echo @$info[0]; ?>
    },
    <?php } ?>
    "datePublished": "<?php echo date(DATE_ISO8601, strtotime($this->item->publish_up)); ?>",
    "dateCreated": "<?php echo date(DATE_ISO8601, strtotime($this->item->created)); ?>",
    "dateModified": "<?php echo date(DATE_ISO8601, strtotime($this->item->modified)); ?>",
    "author": {
        "@type": "Person",
        "name": "<?php echo $article->author; ?>"
    },
    "publisher": {
        "@type": "Organization",
          "logo": {
            "@type": "ImageObject",
            "height": "<?php echo $logoHeight; ?>",
            "width": "<?php echo $logoWidth; ?>",
            "url": "<?php echo $logoUrl; ?>"
          },
        "name": "<?php echo $config->get('sitename'); ?>"
    }
}

    </script>
    <?php
    $articleLdJson = ob_get_clean();
    JFactory::getDocument()->addCustomTag($articleLdJson);
}
// return if post is nicepage article
if (property_exists($this->item, 'nicepage')) {
    echo $article->text($article->text);
    return;
}
$fileName = $themeParams->get('post', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/' . $fileName . '.php';
}
