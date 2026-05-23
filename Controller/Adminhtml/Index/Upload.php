<?php

namespace Digidennis\FabricColors\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Upload extends Action
{
    protected $jsonFactory;
    protected $uploaderFactory;
    protected $filesystem;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'file']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
            $uploader->setAllowRenameFiles(true);

            $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $path = 'fabriccolors/';

            $upload = $uploader->save($media->getAbsolutePath($path));

            return $result->setData([
                'file' => $upload['file'],
                'url'  => '/media/' . $path . $upload['file']
            ]);

        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }
    }
}
