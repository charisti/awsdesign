#!/usr/bin/env bash
set -e
BASE="web/themes/custom/gin_frontend"

mkdir -p "$BASE"/{css,templates}

cat > "$BASE/gin_frontend.info.yml" <<'YML'
name: 'Gin Frontend'
type: theme
description: 'Frontend subtheme of Gin for site-specific templates and styles.'
package: Custom
core_version_requirement: ^10 || ^11
base theme: gin
libraries:
  - gin_frontend/global-styling
regions:
  header: 'Header'
  navigation: 'Navigation'
  content: 'Content'
  sidebar_first: 'Sidebar first'
  footer: 'Footer'
YML

cat > "$BASE/gin_frontend.libraries.yml" <<'YML'
global-styling:
  css:
    theme:
      css/style.css: {}
  version: 1.x

work-item:
  css:
    theme:
      css/work-item.css: {}
  version: 1.x
YML

cat > "$BASE/css/style.css" <<'CSS'
/* gin_frontend global overrides */
:root {
  --brand-color: #0b4a7a;
  --muted: #666;
}
body {
  color: #222;
  background: #fff;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}
/* Add site-wide overrides here */
CSS

cat > "$BASE/css/work-item.css" <<'CSS'
/* Work item card/detail styles */
.work-item {
  background: #fff;
  border: 1px solid #e6e6e6;
  border-radius: 10px;
  padding: 1.25rem;
  max-width: 900px;
  margin: 0 auto 1.5rem;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.work-item__header { display:flex; justify-content:space-between; gap:1rem; margin-bottom:.5rem; }
.work-item__title { margin:0; font-size:1.5rem; }
.badge { display:inline-block; padding:.25rem .5rem; border-radius:6px; font-size:.85rem; font-weight:600; }
.badge--status { background:#eef6ff; color:var(--brand-color); margin-left:.5rem; }
.badge--priority.low { background:#e8f5e9; color:#1b5e20; }
.badge--priority.medium { background:#fff8e1; color:#6a4f00; }
.badge--priority.high { background:#fff3e0; color:#e65100; }
.badge--priority.critical { background:#ffebee; color:#b71c1c; }
.work-item__meta { display:flex; gap:1rem; color:var(--muted); margin-bottom:.75rem; font-size:.95rem; }
.work-item__body { margin-bottom:1rem; color:#222; line-height:1.6; }
.work-item__progress { position:relative; background:#f1f1f1; height:12px; border-radius:8px; overflow:hidden; margin:.5rem 0 1rem; }
.progress__bar { height:100%; background:linear-gradient(90deg,#4caf50,#2e7d32); width:0%; transition:width .6s ease; }
.progress__label { position:absolute; right:.5rem; top:-1.6rem; font-size:.85rem; color:#333; }
.work-item__footer { display:flex; gap:1rem; flex-wrap:wrap; color:var(--muted); font-size:.95rem; }
@media (max-width:700px) {
  .work-item { padding:1rem; }
  .work-item__header { flex-direction:column; align-items:flex-start; gap:.5rem; }
}
CSS

cat > "$BASE/templates/node--work.html.twig" <<'TWIG'
<article{{ attributes.addClass('work-item', 'work-item--' ~ (node.field_status.value|default('unknown')|clean_class)) }}>
  {{ attach_library('gin_frontend/work-item') }}
  <header class="work-item__header">
    <h1 class="work-item__title">{{ label }}</h1>
    <div class="work-item__badges">
      {% if node.field_status.value %}
        <span class="badge badge--status">{{ node.field_status.value }}</span>
      {% endif %}
      {% if node.field_priority.value %}
        <span class="badge badge--priority badge--{{ node.field_priority.value|lower|clean_class }}">{{ node.field_priority.value }}</span>
      {% endif %}
    </div>
  </header>

  <div class="work-item__meta">
    {% if content.field_assignee %}<div class="work-item__assignee">{{ content.field_assignee }}</div>{% endif %}
    {% if node.field_due_date.value %}<div class="work-item__due"><strong>Due:</strong> {{ node.field_due_date.value|date("Y-m-d") }}</div>{% endif %}
  </div>

  <div class="work-item__body">{{ content.body }}</div>

  {% if node.field_progress.value is defined %}
    <div class="work-item__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ node.field_progress.value }}">
      <div class="progress__bar" style="width: {{ node.field_progress.value|default(0) }}%"></div>
      <div class="progress__label">{{ node.field_progress.value|default(0) }}%</div>
    </div>
  {% endif %}

  <footer class="work-item__footer">
    {% if content.field_tags %}<div class="work-item__tags">{{ content.field_tags }}</div>{% endif %}
    {% if content.field_attachments %}<div class="work-item__attachments">{{ content.field_attachments }}</div>{% endif %}
  </footer>
</article>
TWIG

cat > "$BASE/gin_frontend.theme" <<'PHP'
<?php
/**
 * @file
 * Theme preprocess functions for gin_frontend.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_node().
 */
function gin_frontend_preprocess_node(array &$variables) {
  if (isset($variables['node']) && $variables['node']->getType() === 'work') {
    $priority = $variables['node']->get('field_priority')->value ?? '';
    if (!empty($priority) && isset($variables['attributes']) && is_object($variables['attributes'])) {
      $variables['attributes']->addClass('work-priority-' . Html::getClass($priority));
    }
  }
}
PHP

echo "Created gin_frontend subtheme in $BASE"
echo "Run: ddev start && ddev drush theme:enable gin_frontend && ddev drush config:set system.theme default gin_frontend -y && ddev drush cr"