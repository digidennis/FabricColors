<?php

namespace Digidennis\FabricColors\Console\Command;

use Digidennis\FabricColors\Model\ResourceModel\ColorImage;
use Digidennis\FabricColors\Service\Pixelscanner;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FabricColorsScanCommand extends Command
{
    const ARG_COLOR_ID = 'color_id';
    const OPT_FILE = 'file';

    private $pixelscanner;
    private $colorImageResource;

    public function __construct(
        Pixelscanner $pixelscanner,
        ColorImage $colorImageResource
    ) {
        $this->pixelscanner = $pixelscanner;
        $this->colorImageResource = $colorImageResource;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('fabric:color:scan')
            ->setDescription('Scan a fabric color image and output average color values')
            ->addArgument(
                self::ARG_COLOR_ID,
                InputArgument::OPTIONAL,
                'Fabric color ID'
            )
            ->addOption(
                self::OPT_FILE,
                null,
                InputOption::VALUE_REQUIRED,
                'Absolute path to image file'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileOption = $input->getOption(self::OPT_FILE);
        $colorId = $input->getArgument(self::ARG_COLOR_ID);

        if ($fileOption) {
            $path = $fileOption;
        } elseif ($colorId) {
            $image = $this->colorImageResource->getPrimaryImagePath($colorId);
            if (!$image) {
                $output->writeln("<error>No image found for color_id {$colorId}</error>");
                return Cli::RETURN_FAILURE;
            }
            $path = BP . '/pub/media/' . ltrim($image, '/');
        } else {
            $output->writeln("<error>You must provide either a color_id or --file</error>");
            return Cli::RETURN_FAILURE;
        }

        $output->writeln("<info>Scanning image:</info> {$path}");

        $result = $this->pixelscanner->scan($path);

        if (!$result) {
            $output->writeln("<error>Could not scan image</error>");
            return Cli::RETURN_FAILURE;
        }

        $output->writeln("");
        $output->writeln("<comment>Average Color Values:</comment>");
        $output->writeln("  R:   {$result['avg_color_r']}");
        $output->writeln("  G:   {$result['avg_color_g']}");
        $output->writeln("  B:   {$result['avg_color_b']}");
        $output->writeln("  HEX: {$result['avg_color_hex']}");
        $output->writeln("  LAB: {$result['avg_color_lab']}");
        $output->writeln("");

        return Cli::RETURN_SUCCESS;
    }
}
