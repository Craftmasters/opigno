<?php

namespace Drupal\opigno_statistics;

/**
 * Common helper methods for a statistics pages.
 */
trait StatisticsPageTrait {

  /**
   * Builds circle indicator for a value.
   *
   * @param float $value
   *   From 0 to 1.
   *
   * @return array
   *   Render array.
   */
  protected function buildCircleIndicator($value) {
    $width = 100;
    $height = 100;
    $cx = $width / 2;
    $cy = $height / 2;
    $radius = min($width / 2, $height / 2);

    $value_rad = $value * 2 * 3.14159 - 3.14159 / 2;
    $x = round($cx + $radius * cos($value_rad), 2);
    $y = round($cy + $radius * sin($value_rad), 2);

    if ($value_rad < 3.14159 / 2) {
      $template = '<svg class="indicator" viewBox="0 0 {{ width }} {{ height }}">
  <circle cx="{{ cx }}" cy="{{ cy }}" r="{{ radius }}"></circle>
  <path d="M{{ cx }},{{ cy }}
    L{{ cx }},0
    A{{ radius }},{{ radius }} 1 0,1 {{ x }},{{ y }} z"></path>
  <circle class="inner" cx="{{ cx }}" cy="{{ cy }}" r="{{ radius - 6 }}"></circle>
</svg>';
    }
    else {
      $template = '<svg class="indicator" viewBox="0 0 {{ width }} {{ height }}">
  <circle cx="{{ cx }}" cy="{{ cy }}" r="{{ radius }}"></circle>
  <path d="M{{ cx }},{{ cy }}
    L{{ cx }},0
    A{{ radius }},{{ radius }} 1 0,1 {{ cx }},{{ cy + radius }}
    A{{ radius }},{{ radius }} 1 0,1 {{ x }},{{ y }} z"></path>
  <circle class="inner" cx="{{ cx }}" cy="{{ cy }}" r="{{ radius - 6 }}"></circle>
</svg>';
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['indicator-wrapper'],
      ],
      [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'width' => $width,
          'height' => $height,
          'cx' => $cx,
          'cy' => $cy,
          'radius' => $radius,
          'x' => $x,
          'y' => $y,
        ],
      ],
    ];
  }

  /**
   * Builds value for the training progress block.
   *
   * @param string $label
   *   Value label.
   * @param string $value
   *   Value.
   * @param string $help_text
   *   Help text.
   *
   * @return array
   *   Render array.
   */
  protected function buildValue($label, $value, $help_text = NULL) {
    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['value-wrapper'],
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['value', ($help_text) ? 'p-relative' : NULL],
        ],
        '#value' => $value,
        ($help_text) ? [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['popover-help'],
            'data-toggle' => 'popover',
            'data-content' => $help_text,
          ],
          '#value' => '?',
        ] : NULL,
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['label'],
        ],
        '#value' => $label,
      ],
    ];
  }

  /**
   * Builds value with a indicator for the training progress block.
   *
   * @param string $label
   *   Value label.
   * @param float $value
   *   From 0 to 1.
   * @param null|string $value_text
   *   Formatted value (optional).
   * @param string $help_text
   *   Help text.
   *
   * @return array
   *   Render array.
   */
  protected function buildValueWithIndicator($label, $value, $value_text = NULL, $help_text = NULL) {
    $value_text = isset($value_text) ? $value_text : $this->t('@percent%', [
      '@percent' => round(100 * $value),
    ]);

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['value-indicator-wrapper'],
      ],
      'value' => $this->buildValue($label, $value_text, $help_text),
      'indicator' => $this->buildCircleIndicator($value),
    ];
  }

  /**
   * Builds render array for a score value.
   *
   * @param int $value
   *   Score.
   *
   * @return array
   *   Render array.
   */
  protected function buildScore($value) {
    return [
      '#type' => 'container',
      'score' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('@score%', ['@score' => $value]),
      ],
      'score_bar' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['score-bar'],
        ],
        'score_bar_inner' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['score-bar-inner'],
            'style' => "width: $value%;",
          ],
        ],
      ],
    ];
  }

  /**
   * Builds render array for a status value.
   *
   * @param string $value
   *   Status.
   *
   * @return array
   *   Render array.
   */
  protected function buildStatus($value) {
    switch (strtolower($value)) {
      default:
      case 'pending':
        $status_icon = 'icon_state_pending';
        $status_text = $this->t('Pending');
        break;

      case 'failed':
        $status_icon = 'icon_state_failed';
        $status_text = $this->t('Failed');
        break;

      case 'completed':
      case 'passed':
        $status_icon = 'icon_state_passed';
        $status_text = $this->t('Passed');
        break;
    }

    return [
      [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'class' => ['icon_state', $status_icon],
        ],
      ],
      [
        '#markup' => $status_text,
      ],
    ];
  }

}
