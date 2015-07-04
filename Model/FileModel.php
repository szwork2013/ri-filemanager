<?php
/*
 * This file is part of the RIFilemanagerBundle package.
 *
 * (c) Rafal Ignaszewski <https://github.com/qjon>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RI\FileManagerBundle\Model;

use Doctrine\ORM\EntityManager;
use RI\FileManagerBundle\Entity\Directory;
use RI\FileManagerBundle\Entity\File;
use RI\FileManagerBundle\Manager\UploadDirectoryManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileModel
 *
 * @package RI\FileManagerBundle\Model
 */
class FileModel
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UploadDirectoryManager
     */
    private $uploadDirectoryManager;

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var bool
     */
    private $doResize;

    /**
     * @param EntityManager          $entityManager
     * @param UploadDirectoryManager $uploadDirectoryManager
     * @param string                 $rootDir
     * @param bool                   $doResize
     * @param int                    $maxResizeWidth
     */
    public function __construct(EntityManager $entityManager, UploadDirectoryManager $uploadDirectoryManager, $rootDir, $doResize, $maxResizeWidth)
    {
        $this->entityManager = $entityManager;
        $this->uploadDirectoryManager = $uploadDirectoryManager;
        $this->webDir = $rootDir . '/../web';
        $this->doResize = $doResize;
        $this->maxResizeWidth = $maxResizeWidth;
    }

    /**
     * @param string       $filename
     * @param UploadedFile $uploadedFile
     * @param Directory    $directory
     *
     * @return File
     */
    public function save($filename, UploadedFile $uploadedFile, Directory $directory = null)
    {
        $newFilePath = $this->uploadDirectoryManager->getNewPath($filename);
        $size = $uploadedFile->getSize();
        $mimeType = $uploadedFile->getMimeType();

        $uploadedFile->move($this->webDir . dirname($newFilePath), basename($newFilePath));

        $path = $this->webDir . $newFilePath;
        if ($this->doResize) {
            $this->resizeImage($path);
        }
        $fileParams = $this->createFileParams($size, $mimeType, $path);

        $file = new File();
        $file->setName($filename);
        $file->setDirectory($directory);
        $file->setPath($newFilePath);
        $file->setParams($fileParams);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        return $file;
    }

    /**
     * @param string $size
     * @param string $mimeType
     * @param string $path
     *
     * @return UploadedFileParametersModel
     */
    public function createFileParams($size, $mimeType, $path)
    {
        $imageSize = getimagesize($path);

        $fileParams = new UploadedFileParametersModel();
        $fileParams->setSize($size);
        $fileParams->setMime($mimeType);
        $fileParams->setHeight($imageSize[1]);
        $fileParams->setWidth($imageSize[0]);

        return $fileParams;
    }

    /**
     * Resize file if it is image (getimagesize return 0 with for non image file)
     *
     * @param string $path
     */
    public function resizeImage($path)
    {
        $maxWidthHeight = $this->maxResizeWidth;
        $imageData = getimagesize($path);

        if ($imageData[0] !== 0 && ($imageData[0] > $maxWidthHeight || $imageData[1] > $maxWidthHeight)) {
            $imagick = new \Imagick($path);
            $imagick->resizeimage($maxWidthHeight, $maxWidthHeight, \Imagick::FILTER_POINT, 1, true);
            $imagick->writeimage($path);
        }
    }
} 