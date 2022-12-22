<?php


namespace Sintattica\Atk\Core\Menu;


use Sintattica\Atk\Core\Language;
use Sintattica\Atk\Core\Tools;

/**
 * Class Item
 * Fluent Interface to create a new Item.
 * @package Sintattica\Atk\Core\Menu
 * @author N.Gjata
 */
abstract class Item
{

    public const TOOLTIP_PLACEMENT_TOP = "top";
    public const TOOLTIP_PLACEMENT_BOTTOM = "bottom";
    public const TOOLTIP_PLACEMENT_LEFT = "left";
    public const TOOLTIP_PLACEMENT_RIGHT = "right";

    public const ICON_FA = 'fa';
    public const ICON_IMAGE = 'image';
    public const ICON_CHARS = 'chars';

    protected const DEFAULT_PARENT = "main";
    protected const DEFAULT_ORDER = -1;

    protected $uuid;
    protected $name = "";

    protected $parent = self::DEFAULT_PARENT;
    protected $position = MenuBase::MENU_SIDEBAR;
    protected $enable = 1;
    protected $order = self::DEFAULT_ORDER;
    protected $module = '';
    protected $raw = false;
    protected $urlParams = [];
    protected $tooltip = null;
    protected $tooltipPlacement = self::TOOLTIP_PLACEMENT_BOTTOM;

    private $iconType = self::ICON_FA;
    private $hideName = false;

    // TODO: how to handle external links?
    protected $icon = null;
    protected $active = false;

    public function __construct()
    {
    }

    protected abstract function createIdentifierComponents(): ?string;

    public function getIdentifier(): ?string
    {
        if (!$this->uuid) {
            $this->uuid = self::generateHash($this->position . $this->parent . $this->createIdentifierComponents());
        }

        return $this->uuid;
    }

    protected static function generateHash($string, $hashLength = 6)
    {
        $fullHash = md5($string);
        return $hashLength ? substr($fullHash, 0, $hashLength) : $fullHash;
    }

    /**
     * Called only by children as this class is abstract
     * @return string
     */
    public function getType(): string
    {
        try {
            return Tools::getClassName($this);
        } catch (\Exception $e) {
            //NOOP -- this cannot happen!
        }

        return "";
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function setParent(string $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return int|array|string
     */
    public function getEnable()
    {
        return $this->enable;
    }

    public function setEnable(int $enable): self
    {
        $this->enable = $enable;
        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(int $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setModule(string $module): self
    {
        $this->module = $module;
        return $this;
    }

    public function isRaw(): bool
    {
        return $this->raw;
    }

    public function setRaw(bool $raw): self
    {
        $this->raw = $raw;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     * @param string|null $iconType
     * @return Item
     */
    public function setIcon(?string $icon, ?string $iconType = self::ICON_FA): self
    {
        switch ($iconType) {
            case self::ICON_IMAGE:
                $this->iconType = self::ICON_IMAGE;
                $this->icon = $icon;
                break;
            case self::ICON_CHARS:
                $this->iconType = self::ICON_CHARS;
                $this->icon = substr(Tools::atktext($icon, '', '', Language::getLanguage()), 0, 2);
                break;
            default:
                $this->iconType = self::ICON_FA;
                $this->icon = $icon;
        }

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getIconType(): string
    {
        return $this->iconType;
    }

    public function isNameHidden(): bool
    {
        return $this->hideName;
    }

    public function hideName(bool $showItemName = true): self
    {
        $this->hideName = $showItemName;
        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function setTooltip(string $tooltip): self
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function getTooltipPlacement(): string
    {
        return $this->tooltipPlacement;
    }

    public function setTooltipPlacement(string $tooltipPlacement): self
    {
        $this->tooltipPlacement = $tooltipPlacement;
        return $this;
    }
}
