<?php

namespace core\views;

class About_view
{
    static public function stageOne()
    {
        ob_start();
        ?>
        <section class="stage-one">
            <div class="row">
                <h3>Stage one</h3>
            </div>
            <div class="row">
                <div class="col s12 m2">
                    <p class="date">Start date:<br>November 27, 2017</p>
                    <p class="date">End date:<br>December 07, 2017</p>
                </div>
                <div class="col s12 m10">
                    <div class="row">
                        <p class="header">Discount:</p>
                        <p>The discount is offered throughout the 1st stage (November 27 through December 7) and will be
                            40% for the first day and 20% thereafter.</p>
                    </div>
                    <div class="row">
                        <p class="header">Purchase limits:</p>
                        <p>
                            The minimum contribution limit remains the same as during presale – 50 USD (in CPT
                            equivalent).<br>
                            Both existing participants who registered themselves during presale and new participants can
                            take part in the token sale.
                        </p>
                    </div>
                    <div class="row">
                        <p class="header">Referral Program:</p>
                        <p>
                            Anyone can get registered and participate in the token sale (see Terms and Conditions for
                            legal restrictions). A new participant can get registered either directly at the web page of
                            the project or using a group invite link from an existing participant. Newly registered
                            participants can invite further potential participants by sending them their group invite
                            links.<br>
                            The following reward system is applicable to all participants (newly registered or
                            registered earlier) who have at least 10000 CPTs in their Cryptaur wallet (cumulatively from
                            all the previous presale and sale stages) on the CPTs purchased during the token sale by
                            those they invited:
                        </p>
                    </div>
                    <div class="row">
                        <p class="header">From Level 1: 3%</p>
                        <p class="header">From Level 2: 3%</p>
                        <p class="header">From Level 3: 3%</p>
                        <p class="header">From Level 4: 3%</p>
                        <p class="header">From Level 5: 4%</p>
                        <p class="header">From Level 6: 4%</p>
                    </div>
                    <div class="row">
                        <p>
                            The bounty is awarded with the compression procedure applied to participants who have at
                            least 10000 CPTs. Bounties for the purchases made during the 1st Stage will be calculated at
                            the end of the 1st Stage with the compression procedure applied.<br>
                            The bounty received can be withdrawn to any Ether wallet address or re-invested into CPTs.
                            In case of re-investment the discount will be 40% on the first day and 20% until January 22,
                            including the period between Stage One and Stage Two of the token sale.
                        </p>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}