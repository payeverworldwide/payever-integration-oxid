<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
        $imageIndex = count($existingImages) + 1;
        $fileDir = $this->getConfig()->getConfigParam('sCompileDir');
        foreach ($imagesUrl as $key => $url) {
            if ($imageIndex > 7) {
                break;
            }
            if (empty($imagesName[$key])) {
                continue;
            }
            $filename = $imagesName[$key];
            if (strpos($filename, '.') === false) {
                $filename .= '.png';
            }
            if (
                !in_array($imagesUuid[$key], $existingImagesUuid, true)
                && !in_array($filename, $existingImagesNames, true)
            ) {
                $filePath = rtrim($fileDir, '/') . DS . $filename;
                if ($this->downloadImage($filePath, $url)) {
                    $fieldName = sprintf('oxarticles__oxpic%s', $imageIndex);
                    if (!isset($product->$fieldName) || false === $product->$fieldName) {
                        $product->assign([$fieldName => $fieldName]);
                    }
                    $keyName = sprintf('M%s@%s', $imageIndex, $fieldName);
                    $filesToProcess['error'][$keyName] = 0;
                    $filesToProcess['name'][$keyName] = $filename;
                    $filesToProcess['size'][$keyName] = $this->skipFs ? 0 : filesize($filePath);
                    $filesToProcess['tmp_name'][$keyName] = $filePath;
                    $filesToProcess['type'][$keyName] = $this->skipFs || !function_exists('mime_content_type')
                        ? 'image/jpg'
                        : mime_content_type($filePath);
                    $imageIndex++;
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
            $filePointer = fopen($localPath, 'wb+');
            $curlHandle = curl_init($url);
            curl_setopt($curlHandle, CURLOPT_FILE, $filePointer);
            curl_setopt($curlHandle, CURLOPT_HEADER, 0);
            curl_setopt($curlHandle, CURLOPT_BINARYTRANSFER, 1);
            curl_exec($curlHandle);
            $error = curl_error($curlHandle);
            if ($error) {
                $this->getLogger()->warning(
                    'Unable to download product image',
                    [
                        $url,
                        $error,
                    ]
                );
            }
            curl_close($curlHandle);
            fclose($filePointer);
            $result = !$error;
        }

        return $result;
    }

    /**
     * @param bool $flag
     * @return $this
     * @internal
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function setSkipFs($flag = false)
    {
        $this->skipFs = $flag;

        return $this;
    }

    /**
     * @param oxutilsfile $fileUtil
     * @return $this
     * @internal
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
