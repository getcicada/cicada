<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme\Exception;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('frontend')]
class ThemeAssignmentException extends CicadaHttpException
{
    /**
     * @param array<string, array<int, string>> $themeChannel
     * @param array<string, array<int, string>> $childThemeChannel
     * @param array<string, string> $assignedChannels
     */
    public function __construct(
        string $themeName,
        array $themeChannel,
        array $childThemeChannel,
        private readonly array $assignedChannels,
        ?\Throwable $e = null
    ) {
        $parameters = ['themeName' => $themeName];
        $message = 'Unable to deactivate or uninstall theme "{{ themeName }}".';
        $message .= ' Remove the following assignments between theme and sales channel assignments: {{ assignments }}.';
        $assignments = '';
        if (\count($themeChannel) > 0) {
            $assignments .= $this->formatAssignments($themeChannel);
        }

        if (\count($childThemeChannel) > 0) {
            $assignments .= $this->formatAssignments($childThemeChannel);
        }
        $parameters['assignments'] = $assignments;

        parent::__construct($message, $parameters, $e);
    }

    public function getErrorCode(): string
    {
        return 'THEME__THEME_ASSIGNMENT';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @return array<string, string>|null
     */
    public function getAssignedChannels(): ?array
    {
        return $this->assignedChannels;
    }

    /**
     * @param array<string, array<int, string>> $assignmentMapping
     */
    private function formatAssignments(array $assignmentMapping): string
    {
        $output = [];
        foreach ($assignmentMapping as $themeName => $channelIds) {
            $channelNames = [];
            foreach ($channelIds as $channelId) {
                if ($this->assignedChannels[$channelId]) {
                    $channel = $this->assignedChannels[$channelId];
                } else {
                    $channelNames[] = $channelId;

                    continue;
                }

                $channelNames[] = $channel;
            }

            $output[] = \sprintf('"%s" => "%s"', $themeName, implode(', ', $channelNames));
        }

        return implode(', ', $output);
    }
}
