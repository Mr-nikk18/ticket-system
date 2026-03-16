<?php
$section_shell = isset($section_shell) && is_array($section_shell) ? $section_shell : [];
$shell_id = !empty($section_shell['id']) ? preg_replace('/[^a-z0-9_-]/i', '', (string) $section_shell['id']) : 'workspace';
$default_section = !empty($section_shell['default']) ? (string) $section_shell['default'] : '';
$sections = !empty($section_shell['sections']) && is_array($section_shell['sections']) ? $section_shell['sections'] : [];
$label = !empty($section_shell['label']) ? (string) $section_shell['label'] : 'Workspace Sections';
$eyebrow = !empty($section_shell['eyebrow']) ? (string) $section_shell['eyebrow'] : '';
$title = !empty($section_shell['title']) ? (string) $section_shell['title'] : '';
$description = !empty($section_shell['description']) ? (string) $section_shell['description'] : '';
$badge = !empty($section_shell['badge']) ? (string) $section_shell['badge'] : '';
?>

<div
    class="trs-section-shell"
    data-shell-id="<?php echo htmlspecialchars($shell_id); ?>"
    data-default-section="<?php echo htmlspecialchars($default_section); ?>"
>
    <?php if ($eyebrow !== '' || $title !== '' || $description !== '' || $badge !== '') { ?>
        <div class="trs-section-shell__header">
            <div>
                <?php if ($eyebrow !== '') { ?>
                    <span class="trs-section-shell__eyebrow"><?php echo htmlspecialchars($eyebrow); ?></span>
                <?php } ?>
                <?php if ($title !== '') { ?>
                    <h2 class="trs-section-shell__title"><?php echo htmlspecialchars($title); ?></h2>
                <?php } ?>
                <?php if ($description !== '') { ?>
                    <p class="trs-section-shell__description"><?php echo htmlspecialchars($description); ?></p>
                <?php } ?>
            </div>
            <?php if ($badge !== '') { ?>
                <span class="trs-section-shell__badge"><?php echo htmlspecialchars($badge); ?></span>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!empty($sections)) { ?>
        <div class="trs-section-shell__nav" role="tablist" aria-label="<?php echo htmlspecialchars($label); ?>">
            <?php foreach ($sections as $index => $section) { ?>
                <?php
                $section_id = !empty($section['id']) ? preg_replace('/[^a-z0-9_-]/i', '', (string) $section['id']) : 'section-' . $index;
                $section_label = !empty($section['label']) ? (string) $section['label'] : ucfirst(str_replace(['-', '_'], ' ', $section_id));
                $section_hint = !empty($section['hint']) ? (string) $section['hint'] : '';
                ?>
                <button
                    type="button"
                    class="trs-section-shell__tab"
                    data-section-target="<?php echo htmlspecialchars($section_id); ?>"
                    role="tab"
                    aria-selected="false"
                >
                    <span class="trs-section-shell__tab-label"><?php echo htmlspecialchars($section_label); ?></span>
                    <?php if ($section_hint !== '') { ?>
                        <span class="trs-section-shell__tab-hint"><?php echo htmlspecialchars($section_hint); ?></span>
                    <?php } ?>
                </button>
            <?php } ?>
        </div>
    <?php } ?>
</div>
