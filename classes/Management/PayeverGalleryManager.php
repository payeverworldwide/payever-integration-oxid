<?php
/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

class PayeverGalleryManager
{
    use PayeverConfigTrait;
    use PayeverGenericManagerTrait;

    /** @var oxutilsfile */
    protected $fileUtil;

    /** @var bool Determines whether skip file system operations */
    protected $skipFs = false;

    /**
     * @param oxarticle $product
     * @return array
     */
    public function getGallery($product)
    {
        $gallery = $product->getPictureGallery();

        return !empty($gallery['Pics']) ? array_values($gallery['Pics']) : [];
    }

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity $requestEntity
     * @throws oxSystemComponentException
     */
    public function appendGallery($product, ProductRequestEntity $requestEntity)
    {
        $imagesUrl = $requestEntity->getImagesUrl();
        $imagesUuid = $requestEntity->getImagesUuid();
        $imagesName = $requestEntity->getImages();
        $productGallery = $product->getPictureGallery();
        $existingImages = !empty($productGallery['Pics']) ? array_values($productGallery['Pics']) : [];
        $existingImagesUuid = [];
        $existingImagesNames = [];
        foreach ($existingImages as $existingImageUrl) {
            $name = substr($existingImageUrl, strrpos($existingImageUrl, '/') + 1);
            $existingImagesUuid[] = substr($name, 0, 36);
            $existingImagesNames[] = $name;
        }
        $filesToProcess = [];
        $i = count($existingImages) + 1;
        $fileDir = $this->getConfig()->getConfigParam('sCompileDir');
        foreach ($imagesUrl as $key => $url) {
            if ($i > 7) {
                break;
            }
            $filename = $imagesName[$key];
            if (strpos($filename, '.') === false) {
                $filename .= '.png';
            }
            if (!in_array($imagesUuid[$key], $existingImagesUuid, true)
                && !in_array($filename, $existingImagesNames, true)
            ) {
                $filePath = rtrim($fileDir, '/') . DS . $filename;
                if ($this->downloadImage($filePath, $url)) {
                    $fieldName = sprintf('oxarticles__oxpic%s', $i);
                    if (!isset($product->$fieldName) || false === $product->$fieldName) {
                        $product->assign([$fieldName => $fieldName]);
                    }
                    $keyName = sprintf('M%s@%s', $i, $fieldName);
                    $filesToProcess['error'][$keyName] = 0;
                    $filesToProcess['name'][$keyName] = $filename;
                    $filesToProcess['size'][$keyName] = $this->skipFs ? 0 : filesize($filePath);
                    $filesToProcess['tmp_name'][$keyName] = $filePath;
                    $filesToProcess['type'][$keyName] = $this->skipFs ? 'image/jpg' : mime_content_type($filePath);
                    $i++;
                }
            }
        }
        $this->getFileUtil()->processFiles($product, ['myfile' => $filesToProcess], true);
    }

    /**
     * @param string $localPath
     * @param string $url
     * @return bool
     * @codeCoverageIgnore
     */
    protected function downloadImage($localPath, $url)
    {
        $result = true;
        if (!$this->skipFs) {
            $fp = fopen($localPath, 'wb+');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_exec($ch);
            $error = curl_error($ch);
            if ($error) {
                $this->getLogger()->warning(
                    'Unable to download product image',
                    [
                        $url,
                        $error,
                    ]
                );
            }
            curl_close($ch);
            fclose($fp);
            $result = !$error;
        }

        return $result;
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setSkipFs($flag = false)
    {
        $this->skipFs = $flag;

        return $this;
    }

    /**
     * @param oxutilsfile $fileUtil
     * @return $this
     */
    public function setFileUtil($fileUtil)
    {
        $this->fileUtil = $fileUtil;

        return $this;
    }

    /**
     * @return oxutilsfile
     * @codeCoverageIgnore
     */
    protected function getFileUtil()
    {
        return null === $this->fileUtil
            ? $this->fileUtil = oxRegistry::get('oxUtilsFile')
            : $this->fileUtil;
    }
}
