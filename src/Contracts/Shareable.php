<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

/**
 * Implemented by consumer models (afwd Proposal, Quote, Invoice, Post, ImageAsset…)
 * to become shareable. Mansio never references a consumer class — it only knows
 * this contract.
 */
interface Shareable
{
    /**
     * The shareable's primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * The morph type stored against versions and shares.
     *
     * @return string
     */
    public function getMorphClass();

    /**
     * Recipient-facing title shown on the landing page.
     */
    public function mansioTitle(): string;

    /**
     * Optional owner morph (tenant/account) used for scoping. Null disables scoping.
     */
    public function mansioOwner(): ?object;

    /**
     * Default mime type for versions published without an explicit one.
     */
    public function mansioDefaultMime(): string;
}
