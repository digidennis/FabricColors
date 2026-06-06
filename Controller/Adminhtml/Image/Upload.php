<?php
namespace Digidennis\FabricColors\Controller\Adminhtml\Image;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Upload extends Action
{
    protected $uploaderFactory;
    protected $jsonFactory;
    protected $filesystem;

    public function __construct(
        Action\Context $context,
        UploaderFactory $uploaderFactory,
        JsonFactory $jsonFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->jsonFactory = $jsonFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();

        try {
            if (empty($_FILES['product'])) {
                throw new \Exception('No file uploaded');
            }

            // Udtræk første fil fra Magento Custom Options struktur
            $file = $this->extractFile($_FILES['product']);

            if (!$file || !isset($file['tmp_name'])) {
                throw new \Exception('Invalid file structure');
            }

            // Flad struktur til uploader
            $_FILES['fabric_image'] = $file;

            $uploader = $this->uploaderFactory->create(['fileId' => 'fabric_image']);
            $uploader->setAllowedExtensions(['jpg','jpeg','png','gif','webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);

            $mediaDir = $this->filesystem
                ->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath('fabriccolors/');

            $result = $uploader->save($mediaDir);

            if (!$result) {
                throw new \Exception('File could not be saved');
            }

            $result['url'] = $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA])
                . 'fabriccolors' . $result['file'];

            return $resultJson->setData($result);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function extractFile($files)
    {
        foreach ($files['name']['options'] as $optIndex => $opt) {
            foreach ($opt['values'] as $valIndex => $val) {
                foreach ($val as $fieldName => $name) {
                    return [
                        'name'     => $files['name']['options'][$optIndex]['values'][$valIndex][$fieldName],
                        'type'     => $files['type']['options'][$optIndex]['values'][$valIndex][$fieldName],
                        'tmp_name' => $files['tmp_name']['options'][$optIndex]['values'][$valIndex][$fieldName],
                        'error'    => $files['error']['options'][$optIndex]['values'][$valIndex][$fieldName],
                        'size'     => $files['size']['options'][$optIndex]['values'][$valIndex][$fieldName],
                    ];
                }
            }
        }

        return null;
    }
}
