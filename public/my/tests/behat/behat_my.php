<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to the dashboard.
 *
 * The pointer steps here dispatch pointer events from a script rather than through a standard
 * step. They assert what the grid shows while a drag is still held (the tile following the
 * pointer, the drop preview, a displaced neighbour easing aside), and the standard drag and drop
 * step performs a whole drag as one action, with nothing observable in between. Everything a
 * standard step can do (clicks, key presses) goes through one, and every wait is a spin on the
 * state being waited for, never a fixed pause.
 *
 * @package    core_my
 * @category   test
 * @copyright  2026 Matt Porritt <matt.porritt@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_my extends behat_base {
    /**
     * Wait until a script returns a result that passes a check, and return that result.
     *
     * @param string $script The script to evaluate, as for evaluate_script().
     * @param callable $accept Given the script's result, whether it is the state waited for.
     * @param string $message The failure message when the state is never reached.
     * @return mixed The accepted result.
     */
    protected function wait_for_script_result(string $script, callable $accept, string $message) {
        $result = null;
        try {
            $this->spin(
                function ($context) use ($script, $accept, &$result): bool {
                    $result = $context->evaluate_script($script);
                    return (bool) $accept($result);
                },
                false,
                5,
                new \Exception($message),
            );
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage() . ' ' . json_encode($result));
        }
        return $result;
    }

    /**
     * Whether any of a tile's animations is the position easing animation, still in progress.
     *
     * @param array|null $animations Each animation's duration and play state, as read from the page.
     * @return bool
     */
    protected static function easing_animation_is_running(?array $animations): bool {
        foreach ($animations ?? [] as $animation) {
            if ($animation['duration'] === 180 && $animation['state'] !== 'finished') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the internal name of the block shown in a dashboard tile, as its data-block attribute
     * has it, from the block's title.
     *
     * @param string $title The block title, as the "block" selector takes it.
     * @return string The block name, for example calendar_month.
     */
    protected function get_tile_block_name(string $title): string {
        $name = $this->find('block', $title)->getAttribute('data-block');
        if (!$name) {
            throw new \Exception("The '{$title}' block is not a dashboard tile.");
        }
        return $name;
    }

    /**
     * Put a Text (block_html) block with the given title and content on a user's own dashboard.
     *
     * Created directly rather than through the block's configuration form, because the editor
     * would rewrite the content (TinyMCE strips script, for one), and because the point is to
     * test what the dashboard does with content the block has already saved.
     *
     * @Given the dashboard of :username has a text block :title containing :html
     * @param string $username The user's username.
     * @param string $title The block title.
     * @param string $html The block content, as HTML.
     */
    public function the_dashboard_of_user_has_a_text_block(string $username, string $title, string $html): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/my/lib.php');
        $user = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $page = my_copy_page($user->id, MY_PAGE_PRIVATE);
        if (!$page) {
            throw new \Exception("The dashboard page of '{$username}' could not be set up.");
        }
        testing_util::get_data_generator()->create_block('html', [
            'parentcontextid' => context_user::instance($user->id)->id,
            'pagetypepattern' => 'my-index',
            'subpagepattern' => (string) $page->id,
            'defaultregion' => 'content',
            'defaultweight' => 0,
            'configdata' => base64_encode(serialize((object) [
                'title' => $title,
                'text' => $html,
                'format' => FORMAT_HTML,
            ])),
        ]);
    }

    /**
     * Assert the state of the page-level dashboard editing switch.
     *
     * @Then the dashboard edit mode switch should be :state
     * @param string $state Either 'on' or 'off'.
     */
    public function dashboard_edit_mode_switch_should_be(string $state): void {
        $expected = $state === 'on';
        $this->spin(
            function ($context) use ($expected): bool {
                return $context->evaluate_script(<<<'JS'
                    return document.querySelector('input[name="setmode"]')?.checked === true;
                JS) === $expected;
            },
            false,
            5,
            new \Exception("The dashboard edit mode switch is not {$state}.")
        );
    }

    /**
     * Drag a dashboard block one column into free grid space and verify its saved position.
     *
     * @Then dragging the :block dashboard block into an adjacent free column persists
     * @param string $block Block name.
     */
    public function dragging_dashboard_block_into_an_adjacent_free_column_persists(string $block): void {
        $drag = $this->evaluate_script(<<<JS
            return (() => {
                const tile = document.querySelector('.core-my-dashboard-tile[data-block="{$block}"]');
                const handle = tile?.querySelector('.core-my-dashboard-handle--move');
                const grid = document.querySelector('.core-my-dashboard-grid');
                if (!tile || !handle || !grid) {
                    return null;
                }
                const tilerect = tile.getBoundingClientRect();
                const handlerect = handle.getBoundingClientRect();
                const gridrect = grid.getBoundingClientRect();
                const columns = Number(grid.dataset.columns);
                const gap = parseFloat(getComputedStyle(grid).gap);
                const stride = (gridrect.width - gap * (columns - 1)) / columns + gap;
                const column = Number(getComputedStyle(tile).gridColumnStart);
                const span = Number(getComputedStyle(tile).gridColumnEnd.replace('span ', ''));
                const direction = column + span <= columns ? 1 : -1;
                if (direction < 0 && column <= 1) {
                    return {error: 'The fixture does not have an adjacent free column.'};
                }
                const x = handlerect.left + handlerect.width / 2;
                const y = handlerect.top + handlerect.height / 2;
                const pointer = (type, clientx, buttons) => new PointerEvent(type, {
                    bubbles: true,
                    button: 0,
                    buttons,
                    clientX: clientx,
                    clientY: y,
                    isPrimary: true,
                    pointerId: 3,
                    pointerType: 'mouse',
                });
                handle.dispatchEvent(pointer('pointerdown', x, 1));
                window.dispatchEvent(pointer('pointermove', x + direction * stride * 0.75, 1));
                return {column, expected: column + direction, x, y, stride, direction};
            })();
        JS);

        if ($drag === null || isset($drag['error'])) {
            throw new \Exception($drag['error'] ?? 'The dashboard move handle was not found.');
        }

        $script = <<<JS
            return (() => {
                const tile = document.querySelector('.core-my-dashboard-tile[data-block="{$block}"]');
                return {
                    dragging: tile?.classList.contains('core-my-dashboard-tile--pointer-dragging'),
                    transform: tile?.style.transform,
                };
            })();
        JS;
        $this->wait_for_script_result(
            $script,
            static fn($preview): bool => !empty($preview['dragging']) && !empty($preview['transform']),
            'Dashboard move did not produce a fluid drag preview:',
        );
        $this->evaluate_script(<<<JS
            window.dispatchEvent(new PointerEvent('pointerup', {
                bubbles: true,
                button: 0,
                buttons: 0,
                clientX: {$drag['x']} + {$drag['direction']} * {$drag['stride']} * 0.75,
                clientY: {$drag['y']},
                isPrimary: true,
                pointerId: 3,
                pointerType: 'mouse',
            }));
            return true;
        JS);
        $script = <<<JS
            return Number(
                getComputedStyle(document.querySelector('.core-my-dashboard-tile[data-block="{$block}"]')).gridColumnStart
            );
        JS;
        $this->wait_for_script_result(
            $script,
            static fn($actual): bool => (int) $actual === (int) $drag['expected'],
            "Dashboard block '{$block}' did not move to grid column '{$drag['expected']}'. It starts in column:",
        );
    }

    /**
     * Assert that activating a block's resize handle from the keyboard shows the four direction
     * controls, and that Escape puts them away again.
     *
     * @Then keyboard activation of the :block block resize handle shows its directional controls
     * @param string $block The block title.
     */
    public function keyboard_activation_shows_resize_directional_controls(string $block): void {
        $handle = $this->find('button', get_string('resizeblock', 'my'), false, $this->find('block', $block));
        $handle->focus();
        $count = 'return document.querySelectorAll(".core-my-grid-controls__direction").length;';

        $this->execute('behat_general::i_press_named_key', ['', 'enter']);
        $this->wait_for_script_result(
            $count,
            static fn($controls): bool => (int) $controls === 4,
            'Keyboard activation did not show all four resize directional controls. Controls shown:',
        );

        $this->execute('behat_general::i_press_named_key', ['', 'escape']);
        $this->wait_for_script_result(
            $count,
            static fn($controls): bool => (int) $controls === 0,
            'Escape did not put the resize directional controls away. Controls shown:',
        );
    }

    /**
     * Assert that a pointer resize which grows a block stays fluid until it crosses a grid-cell
     * threshold, with its drop preview showing throughout.
     *
     * @Then a pointer resize of the :block block follows the pointer before snapping to a grid cell
     * @param string $block The block title.
     */
    public function pointer_resize_follows_the_pointer(string $block): void {
        $name = $this->get_tile_block_name($block);
        $start = $this->evaluate_script(str_replace('BLOCKNAME', $name, <<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='BLOCKNAME']");
                const handle = tile?.querySelector('.core-my-dashboard-handle--resize');
                const grid = document.querySelector('.core-my-dashboard-grid');
                if (!tile || !handle || !grid) {
                    return null;
                }

                const tilerect = tile.getBoundingClientRect();
                const gridrect = grid.getBoundingClientRect();
                const columns = Number(grid.dataset.columns);
                const gap = parseFloat(getComputedStyle(grid).gap);
                const cellwidth = (gridrect.width - gap * (columns - 1)) / columns;
                const pointer = (type, x) => new PointerEvent(type, {
                    bubbles: true,
                    button: 0,
                    buttons: type === 'pointerup' || type === 'pointercancel' ? 0 : 1,
                    clientX: x,
                    clientY: tilerect.bottom - 20,
                    isPrimary: true,
                    pointerId: 1,
                    pointerType: 'mouse',
                });
                const x = tilerect.right - 20;
                handle.dispatchEvent(pointer('pointerdown', x));
                window.dispatchEvent(pointer('pointermove', x + (cellwidth + gap) * 0.4));
                return {x, y: tilerect.bottom - 20, stride: cellwidth + gap, width: tilerect.width};
            })();
        JS));

        if ($start === null) {
            throw new \Exception("The resize handle of the '{$block}' block was not found.");
        }

        // The tile follows the pointer freely, and the drop preview shows where it would land
        // throughout a pointer resize, including before it has crossed into another cell.
        $script = str_replace('BLOCKNAME', $name, <<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='BLOCKNAME']");
                return {
                    dragging: tile?.classList.contains('core-my-dashboard-tile--pointer-dragging'),
                    width: parseFloat(tile?.style.width || '0'),
                    prospective: document.querySelectorAll('.core-my-grid-cell--prospective').length,
                    controls: document.querySelectorAll('.core-my-grid-controls__direction').length,
                };
            })();
        JS);
        $this->wait_for_script_result(
            $script,
            static fn($state): bool => !empty($state['dragging']) && $state['width'] > $start['width']
                && $state['prospective'] > 0 && $state['controls'] === 0,
            'Resize did not stay fluid, with its drop preview, below the grid-cell threshold:',
        );

        $this->evaluate_script(<<<JS
            window.dispatchEvent(new PointerEvent('pointermove', {
                bubbles: true,
                buttons: 1,
                clientX: {$start['x']} + {$start['stride']} * 0.6,
                clientY: {$start['y']},
                isPrimary: true,
                pointerId: 1,
                pointerType: 'mouse',
            }));
            return true;
        JS);
        $script = <<<'JS'
            return {
                prospective: document.querySelectorAll('.core-my-grid-cell--prospective').length,
                controls: document.querySelectorAll('.core-my-grid-controls__direction').length,
            };
        JS;
        $this->wait_for_script_result(
            $script,
            static fn($state): bool => $state['prospective'] > 0 && $state['controls'] === 0,
            'Pointer resize controls or snap preview are incorrect after crossing the threshold:',
        );
        $this->evaluate_script(<<<'JS'
            window.dispatchEvent(new PointerEvent('pointercancel', {
                bubbles: true,
                pointerId: 1,
                pointerType: 'mouse',
            }));
            return true;
        JS);
    }

    /**
     * Assert that a pointer resize which shrinks a block shows the cells the block still occupies,
     * before it has crossed a grid-cell threshold. The block has to be wider than the minimum
     * width, or its preview could never get narrower.
     *
     * @Then a pointer resize shrinking the :block block shows the cells it still occupies
     * @param string $block The block title.
     */
    public function pointer_resize_shrinking_shows_occupied_cells(string $block): void {
        $name = $this->get_tile_block_name($block);
        $shrinkstart = $this->evaluate_script(str_replace('BLOCKNAME', $name, <<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='BLOCKNAME']");
                const handle = tile?.querySelector('.core-my-dashboard-handle--resize');
                const grid = document.querySelector('.core-my-dashboard-grid');
                if (!tile || !handle || !grid) {
                    return null;
                }

                const tilerect = tile.getBoundingClientRect();
                const gridrect = grid.getBoundingClientRect();
                const columns = Number(grid.dataset.columns);
                const gap = parseFloat(getComputedStyle(grid).gap);
                const stride = (gridrect.width - gap * (columns - 1)) / columns + gap;
                const x = tilerect.right - 20;
                const y = tilerect.bottom - 20;
                const pointer = (type, clientx) => new PointerEvent(type, {
                    bubbles: true,
                    buttons: 1,
                    clientX: clientx,
                    clientY: y,
                    isPrimary: true,
                    pointerId: 2,
                    pointerType: 'mouse',
                });
                handle.dispatchEvent(pointer('pointerdown', x));
                window.dispatchEvent(pointer('pointermove', x - stride * 0.4));
                return {width: tilerect.width};
            })();
        JS));

        if ($shrinkstart === null) {
            throw new \Exception("The resize handle of the '{$block}' block was not found.");
        }

        $script = str_replace('BLOCKNAME', $name, <<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='BLOCKNAME']");
                return {
                    width: parseFloat(tile?.style.width || '0'),
                    prospective: document.querySelectorAll('.core-my-grid-cell--prospective').length,
                };
            })();
        JS);
        $this->wait_for_script_result(
            $script,
            static fn($state): bool => $state['width'] > 0 && $state['width'] < $shrinkstart['width']
                && $state['prospective'] > 0,
            'Resize did not show its occupied cells while shrinking below the snap threshold:',
        );
        $this->evaluate_script(<<<'JS'
            window.dispatchEvent(new PointerEvent('pointercancel', {
                bubbles: true,
                pointerId: 2,
                pointerType: 'mouse',
            }));
            return true;
        JS);
    }

    /**
     * Assert that a pointer resize eases blocks that are displaced by its new grid footprint.
     *
     * A resize handle only ever grows a tile rightwards from its fixed left edge, so the block
     * needs another block in the way of that growth to produce a bump: one hard against the
     * grid's right edge has no room to grow into and would never collide with anything.
     *
     * @Then blocks bumped by a pointer resize of the :block block ease into their new grid position
     * @param string $block The title of the block to grow into its row neighbour.
     */
    public function bumped_blocks_ease_into_their_new_grid_position(string $block): void {
        $name = $this->get_tile_block_name($block);
        $start = $this->evaluate_script(str_replace('BLOCKNAME', $name, <<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='BLOCKNAME']");
                const handle = tile?.querySelector('.core-my-dashboard-handle--resize');
                const grid = document.querySelector('.core-my-dashboard-grid');
                if (!tile || !handle || !grid) {
                    return null;
                }
                const tileRect = tile.getBoundingClientRect();
                const gridRect = grid.getBoundingClientRect();
                const columns = Number(grid.dataset.columns);
                const gap = parseFloat(getComputedStyle(grid).gap);
                const stride = (gridRect.width - gap * (columns - 1)) / columns + gap;
                const x = tileRect.right - 20;
                const y = tileRect.bottom - 20;
                const pointer = (type, clientX, buttons) => new PointerEvent(type, {
                    bubbles: true,
                    button: 0,
                    buttons,
                    clientX,
                    clientY: y,
                    isPrimary: true,
                    pointerId: 4,
                    pointerType: 'mouse',
                });
                handle.dispatchEvent(pointer('pointerdown', x, 1));
                window.dispatchEvent(pointer('pointermove', x + stride * 0.75, 1));
                return {x, y};
            })();
        JS));

        if ($start === null) {
            throw new \Exception("The resize handle of the '{$block}' block was not found.");
        }

        $script = <<<'JS'
            return (() => {
                const tile = document.querySelector('.core-my-dashboard-tile[data-bumped="true"]');
                if (!tile) {
                    return null;
                }
                return tile.getAnimations().map(animation => ({
                    duration: animation.effect?.getTiming().duration,
                    state: animation.playState,
                }));
            })();
        JS;
        $this->wait_for_script_result(
            $script,
            self::easing_animation_is_running(...),
            'A bumped dashboard tile did not run its position easing animation:',
        );

        $returning = $this->evaluate_script(<<<JS
            return (() => {
                const tile = document.querySelector('.core-my-dashboard-tile[data-bumped="true"]');
                const grid = document.querySelector('.core-my-dashboard-grid');
                const handle = document.querySelector(
                    ".core-my-dashboard-tile[data-block='{$name}'] .core-my-dashboard-handle--resize"
                );
                if (!tile || !grid || !handle) {
                    return null;
                }
                const gridRect = grid.getBoundingClientRect();
                const columns = Number(grid.dataset.columns);
                const gap = parseFloat(getComputedStyle(grid).gap);
                const stride = (gridRect.width - gap * (columns - 1)) / columns + gap;
                window.dispatchEvent(new PointerEvent('pointermove', {
                    bubbles: true,
                    buttons: 1,
                    clientX: {$start['x']} + stride * 0.4,
                    clientY: {$start['y']},
                    isPrimary: true,
                    pointerId: 4,
                    pointerType: 'mouse',
                }));
                return {block: tile.dataset.block};
            })();
        JS);
        if ($returning === null) {
            throw new \Exception('The bumped dashboard tile did not remain available for the return animation.');
        }
        $script = <<<JS
            return (() => {
                const tile = document.querySelector('.core-my-dashboard-tile[data-block="{$returning['block']}"]');
                if (!tile) {
                    return null;
                }
                return tile.getAnimations().map(animation => ({
                    duration: animation.effect?.getTiming().duration,
                    state: animation.playState,
                }));
            })();
        JS;
        $this->wait_for_script_result(
            $script,
            self::easing_animation_is_running(...),
            'A returning dashboard tile did not run its position easing animation:',
        );
        $this->evaluate_script(<<<JS
            window.dispatchEvent(new PointerEvent('pointercancel', {
                bubbles: true,
                pointerId: 4,
                pointerType: 'mouse',
                clientX: {$start['x']},
                clientY: {$start['y']},
            }));
            return true;
        JS);
    }

    /**
     * Assert that the four resize controls are centred around their active handle, and that each
     * control's icon is centred within it.
     *
     * @Then the resize directional controls are centred on the active resize handle
     */
    public function resize_directional_controls_are_centred(): void {
        $geometry = $this->evaluate_script(<<<'JS'
            return (() => {
                const handle = document.querySelector('.core-my-dashboard-handle--resize[aria-pressed="true"]');
                const controls = handle?.closest('.core-my-dashboard-handle-wrapper')?.querySelector('.core-my-grid-controls');
                if (!handle || !controls) {
                    return null;
                }

                const centre = rect => ({x: rect.left + rect.width / 2, y: rect.top + rect.height / 2});
                const handlecentre = centre(handle.getBoundingClientRect());
                const directions = ['up', 'left', 'right', 'down'];
                const offsets = Object.fromEntries(directions.map(direction => {
                    const control = controls.querySelector(`.core-my-grid-controls__direction--${direction}`);
                    const controlcentre = centre(control.getBoundingClientRect());
                    const iconcentre = centre(control.querySelector('i').getBoundingClientRect());
                    return [direction, {
                        x: controlcentre.x - handlecentre.x,
                        y: controlcentre.y - handlecentre.y,
                        iconx: iconcentre.x - controlcentre.x,
                        icony: iconcentre.y - controlcentre.y,
                        controlwidth: control.getBoundingClientRect().width,
                        controlheight: control.getBoundingClientRect().height,
                    }];
                }));

                return {offsets};
            })();
        JS);

        if ($geometry === null) {
            throw new \Exception('The active resize handle or its directional controls were not found.');
        }

        $offsets = $geometry['offsets'];
        $tolerance = 1;
        if (
            abs($offsets['up']['x']) > $tolerance || abs($offsets['down']['x']) > $tolerance ||
                abs($offsets['left']['y']) > $tolerance || abs($offsets['right']['y']) > $tolerance
        ) {
            throw new \Exception('Resize directional controls are not centred: ' . json_encode($offsets));
        }

        foreach ($offsets as $offset) {
            if (abs($offset['iconx']) > $tolerance || abs($offset['icony']) > $tolerance) {
                throw new \Exception('Resize directional icons are not centred in their controls: ' . json_encode($offsets));
            }
            if ($offset['controlwidth'] < 24 || $offset['controlheight'] < 24) {
                throw new \Exception('Resize directional controls are too small to target: ' . json_encode($offsets));
            }
        }
    }

    /**
     * Constrain the dashboard grid region to a given width without changing the viewport, so
     * that the grid's column count has to follow its own width rather than the viewport's.
     *
     * @When I constrain the dashboard grid region to :width pixels wide
     * @param int $width The width, in pixels.
     */
    public function i_constrain_the_dashboard_grid_region(int $width): void {
        $constrained = $this->evaluate_script(<<<JS
            return (() => {
                const grid = document.querySelector('.core-my-dashboard-grid');
                if (!grid) {
                    return null;
                }
                grid.style.width = '{$width}px';
                grid.style.maxWidth = '{$width}px';
                return window.innerWidth;
            })();
        JS);
        if ($constrained === null) {
            throw new \Exception('The dashboard grid was not found.');
        }
    }

    /**
     * Assert that the viewport is at least as wide as the given width, to make explicit that a
     * narrower grid in the step above is not a narrower viewport.
     *
     * @Then the viewport should be at least :width pixels wide
     * @param int $width The width, in pixels.
     */
    public function the_viewport_should_be_at_least_pixels_wide(int $width): void {
        $actual = (int) $this->evaluate_script('return window.innerWidth;');
        if ($actual < $width) {
            throw new \Exception("The viewport is {$actual}px wide, expected at least {$width}px.");
        }
    }

    /**
     * Assert that growing a block into a neighbour pushes that neighbour further the same way,
     * into free grid space, rather than relocating it to the start of the row or of the grid: a
     * neighbour pushed right stays in its row, one pushed down stays in its column.
     *
     * @Then resizing the :block block :direction pushes the :neighbour block the same way
     * @param string $block The title of the block to grow by one cell.
     * @param string $direction Either 'right' or 'down'.
     * @param string $neighbour The title of the block expected to be pushed.
     */
    public function resizing_block_pushes_its_neighbour(string $block, string $direction, string $neighbour): void {
        $direction = strtolower($direction);
        if (!in_array($direction, ['right', 'down'], true)) {
            throw new \Exception("A block is grown 'right' or 'down', not '{$direction}'.");
        }
        // Along the direction of the push the neighbour moves on; across it, it stays put.
        [$along, $across] = $direction === 'right' ? ['column', 'row'] : ['row', 'column'];

        $result = $this->keyboard_resize_and_capture_bumped_tile($block, $this->get_tile_block_name($block), ucfirst($direction));
        if ($result['block'] !== $this->get_tile_block_name($neighbour)) {
            throw new \Exception("Expected the '{$neighbour}' block to be pushed: " . json_encode($result));
        }
        if ($result['before'][$across] !== $result['after'][$across]) {
            throw new \Exception("The pushed neighbour changed {$across} instead of staying in place: " .
                json_encode($result));
        }
        if ($result['after'][$along] <= $result['before'][$along]) {
            throw new \Exception("The pushed neighbour did not move further {$direction} into free space: " .
                json_encode($result));
        }
    }

    /**
     * Assert that Calendar's day cells render at their intended compact height when the block's
     * tile is narrow, rather than the full-page calendar view's much taller day cell.
     *
     * Boost's full calendar view sets a fixed, tall day-cell height via a rule scoped to
     * "#region-main .maincalendar .calendarwrapper td > div". Legacy block regions never
     * rendered inside #region-main, so that rule never reached block content; the dashboard grid
     * does render inside #region-main, so without an explicit override the leaked rule forces a
     * tall day cell and a scrolling month grid regardless of how narrow the tile actually is.
     *
     * @Then the Calendar block renders compact day cells
     */
    public function the_calendar_block_renders_compact_day_cells(): void {
        $result = $this->evaluate_script(<<<'JS'
            return (() => {
                const tile = document.querySelector(".core-my-dashboard-tile[data-block='calendar_month']");
                const content = tile?.querySelector('.core-my-dashboard-tile__content');
                const day = content?.querySelector('.calendartable.calendarmonth td:not(.dayblank) > div');
                if (!tile || !content || !day) {
                    return null;
                }
                return {
                    tilewidth: tile.getBoundingClientRect().width,
                    dayheight: day.getBoundingClientRect().height,
                    scrollheight: content.scrollHeight,
                    clientheight: content.clientHeight,
                };
            })();
        JS);

        if ($result === null) {
            throw new \Exception('Could not find a Calendar day cell to measure.');
        }
        // A compact day cell is a small fraction of the leaked full-page height (previously
        // observed around 166px); comfortably below that without pinning to an exact pixel value.
        // This intentionally does not also assert zero scroll: Calendar's own toolbar (course
        // filter, "New event" button) can still exceed a narrow tile's default row allocation
        // even once day cells are compact, which is a separate density question, not this leak.
        if ($result['dayheight'] >= 60) {
            throw new \Exception('Calendar day cells are not rendering at their compact height: ' .
                json_encode($result));
        }
    }

    /**
     * Grow a block by one grid cell in the given direction, using the same discrete-control click
     * sequence as the existing accessible-controls scenario (activate, direction, activate again
     * to commit), and capture the grid position of whichever other tile moved as a result.
     *
     * The pointer-only "bumped" marker used for the FLIP-easing animation is not set for
     * keyboard-origin interactions, so the displaced tile is identified by diffing positions
     * instead.
     *
     * @param string $blocklabel Human-readable block title, as used in "in the ... block" steps.
     * @param string $blockname Internal block name (data-block attribute value) of the resized block.
     * @param string $direction One of 'Right' or 'Down'.
     * @return array Displaced block name plus its before/after {column, row}.
     */
    protected function keyboard_resize_and_capture_bumped_tile(
        string $blocklabel,
        string $blockname,
        string $direction
    ): array {
        $before = $this->read_dashboard_tile_positions();

        $this->execute('behat_general::i_click_on_in_the', ['Resize block', 'button', $blocklabel, 'block']);
        $this->execute('behat_general::i_click_on_in_the', [$direction, 'button', $blocklabel, 'block']);
        $this->execute('behat_general::i_click_on_in_the', ['Resize block', 'button', $blocklabel, 'block']);

        $after = $this->read_dashboard_tile_positions();
        $displaced = null;
        foreach ($before as $name => $position) {
            if ($name !== $blockname && $position !== ($after[$name] ?? null)) {
                $displaced = $name;
                break;
            }
        }

        if ($displaced === null) {
            throw new \Exception('No other dashboard tile moved as a result of the resize: ' .
                json_encode(['before' => $before, 'after' => $after]));
        }

        return [
            'block' => $displaced,
            'before' => $before[$displaced],
            'after' => $after[$displaced],
        ];
    }

    /**
     * Read the current grid column/row of every dashboard tile.
     *
     * @return array Map of block name to {column, row}.
     */
    protected function read_dashboard_tile_positions(): array {
        return $this->evaluate_script(<<<'JS'
            return Object.fromEntries(
                Array.from(document.querySelectorAll('.core-my-dashboard-tile[data-block]')).map(candidate => {
                    const style = getComputedStyle(candidate);
                    return [candidate.dataset.block, {
                        column: Number(style.gridColumnStart),
                        row: Number(style.gridRowStart),
                    }];
                })
            );
        JS);
    }
}
