<?php

namespace core\views;

class Dashboard_view
{
    static public function view()
    {
        ob_start() ?>

        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Token sale</h3>
                <div class="row">
                    <div class="stage active">
                        <h2>Stage 1</h2>
                        <p>Nov 27, 2017</p>
                        <p>Dec 07, 2017</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 2</h2>
                        <p>Jan 22, 2018</p>
                        <p>Jan 31, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 3</h2>
                        <p>Feb 12, 2018</p>
                        <p>Feb 20, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="stage ">
                        <h2>Stage 4</h2>
                        <p>Mar 05, 2018</p>
                        <p>Mar 12, 2018</p>
                        <div class="col s12 offset-m1 m10 offset-l2 l8 offset-xl3 xl6">
                            <button class="waves-effect waves-light btn btn-learn-more">Learn more</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <div class="row indicators">
                    <div class="col s12 m4">
                        <h4>Total tokens issued</h4>
                        <h3>CPT 3,532,715,580</h3>
                    </div>
                    <div class="col s12 m4">
                        <h4>Total funds raised</h4>
                        <h3>US$ 28,567,845</h3>
                    </div>
                    <div class="col s12 m4">
                        <h4>Total participants</h4>
                        <h3>45,836</h3>
                    </div>
                </div>
                <section class="my-tokens">
                    <div class="row">
                        <h3>My tokens</h3>
                    </div>
                    <div class="row">
                        <div class="col s12 m6 main-panel-block">
                            <h3>Purchased</h3>
                            <div class="amount-wallet">
                                47685.6892841090 CPT
                            </div>
                            <div class="amount input-field">
                                <h5>Contributed</h5>
                                <ul>
                                    <li><span>ETH</span><span>123</span></li>
                                    <li><span>BTC</span><span>132</span></li>
                                    <li><span>LTC</span><span>132</span></li>
                                    <li><span>ETC</span><span>132</span></li>
                                    <li><span>USD</span><span>132</span></li>
                                    <li><h5>Total in USD</h5><h5>$123456</h5></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col s12 m6 main-panel-block">
                            <h3>Bounty</h3>
                            <div class="amount-wallet">
                                476 ETH
                            </div>
                            <div class="amount input-field">
                                <input type="number" name="amount" value="10" min="10" max="40" step="1">
                                <label>select amount</label>
                            </div>
                            <div class="amount input-field">
                                <button class="waves-effect waves-light btn ">Withdraw</button>
                            </div>
                            <div class="amount input-field">
                                <button class="waves-effect waves-light btn ">Reinvest</button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Current stage</h3>
                <div class="current-stage">
                    <h2>Stage 1</h2>
                    <p>Nov 27, 2017</p>
                    <p>Dec 07, 2017</p>
                    <div class="shares">
                        <p>1st day</p>
                        <h2>40% Discount</h2>
                        <p>Invite your friends and earn bounties during the Cryptaur token sale</p>
                    </div>
                    <div class="shares">
                        <p>All other days</p>
                        <h2>20% Discount</h2>
                        <p>Invite your friends and earn bounties during the Cryptaur token sale</p>
                    </div>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <section class="my-contribution">
                    <div class="row">
                        <h3>My contribution</h3>
                    </div>
                    <div class="row">
                        <p>
                            <input type="checkbox" id="debit-card" checked="checked"/>
                            <label for="debit-card">I prefer perchase CPT tokens using my credit or debit card</label>
                            <img src="images/visa.png">
                        </p>
                    </div>
                    <div class="row">
                        <p>To learn about the minimum contribution limits <a href="#">click here</a></p>
                    </div>
                    <div class="row">
                        <div class="col s12 offset-m3 m6">
                            <div class="row">
                                <div class="input-field col s12 m4">
                                    <select>
                                        <option value="ETH" selected>ETH</option>
                                        <option value="BTC">BTC</option>
                                    </select>
                                    <label>select currency</label>
                                </div>
                                <div class="input-field col s12 m4">
                                    <input type="number" name="amount" value="10" min="10" max="40" step="1">
                                    <label>select amount</label>
                                </div>
                                <div class="input-field col s12 m4">
                                    <button class="waves-effect waves-light btn btn-contribute">Contribute</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <p>Copy address below to send 10 ETH</p>
                        <h5>0x2b2732Efb676f4660d31F8c7D7e418D4345B17C3</h5>
                        <p>You will get: 320,307.345 CPT</p>
                        <p>Time left: 23 min</p>
                        <div class="input-field col s12 offset-m5 m2 center">
                            <button class="waves-effect waves-light btn btn-send">Send</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <div class="row">
            <div class="col s12 m3 left-panel">
                <h3>Referral program</h3>
                <div class="referral-progam">
                    <div class="line-left"></div>
                    <div class="line-bottom"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <div class="circle"></div>
                    <ul>
                        <li><h2>1st Level: 3%</h2></li>
                        <li><h2>2st Level: 3%</h2></li>
                        <li><h2>3st Level: 3%</h2></li>
                        <li><h2>4st Level: 3%</h2></li>
                        <li><h2>5st Level: 4%</h2></li>
                        <li><h2>6st Level: 4%</h2></li>
                    </ul>
                </div>
            </div>
            <div class="col s12 m9 main-panel">
                <section class="my-group">
                    <div class="row">
                        <h3>My group</h3>
                    </div>
                    <div class="row">
                        <div class="col s12 m4 main-panel-block">
                            <h3>Raised by group: US$ 123,567.89</h3>
                        </div>
                        <div class="col s12 m4 main-panel-block">
                            <h3>Group participants: 247</h3>
                        </div>
                        <div class="col s12 m4 main-panel-block">
                            <div class="input-field">
                                <button class="waves-effect waves-light btn ">Invite friends</button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="main-panel-block tree">
                            <ul class="first-level">
                                <li class="first-level">
                                    <div class="tree-block">
                                        <h2>Sponsor name</h2>
                                        <p>Contributed</p>
                                        <h3>US$ 12399.546</h3>
                                    </div>
                                    <div class="line-bottom"></div>
                                </li>
                                <li>
                                    <ul class="second-level">
                                        <li class="second-level">
                                            <div class="tree-block">
                                                <h2>My name</h2>
                                                <p>Contributed</p>
                                                <h3>US$ 12399.546</h3>
                                            </div>
                                            <div class="line-left"></div>
                                            <div class="line-bottom"></div>
                                        </li>
                                        <li>
                                            <ul class="third-level">
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>15 participants in level<i class="material-icons">expand_more</i>
                                                        </h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                </li>
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>46 participants in level<i class="material-icons">expand_more</i>
                                                        </h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                </li>
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>8 participants in
                                                            level<i class="material-icons">expand_more</i></h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                </li>
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>17 participants in level<i class="material-icons">expand_more</i>
                                                        </h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                </li>
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>150 participants in level<i class="material-icons">expand_more</i>
                                                        </h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                </li>
                                                <li class="third-level">
                                                    <div class="tree-block">
                                                        <h2>My name</h2>
                                                        <p>Contributed</p>
                                                        <h3>US$ 12399.546</h3>
                                                    </div>
                                                    <div class="line-right"></div>
                                                    <div class="participants">
                                                        <h2>79 participants in level<i class="material-icons">expand_more</i>
                                                        </h2>
                                                    </div>
                                                    <div class="line-left"></div>
                                                    <div class="line-bottom"></div>
                                                </li>
                                                <li>
                                                    <ul class="fourth-level">
                                                        <li class="fourth-level">
                                                            <div class="tree-block">
                                                                <h2>My name</h2>
                                                                <p>Contributed</p>
                                                                <h3>US$ 12399.546</h3>
                                                            </div>
                                                            <div class="line-right"></div>
                                                            <div class="participants">
                                                                <h2>10 participants in level<i class="material-icons">expand_more</i>
                                                                </h2>
                                                            </div>
                                                            <div class="line-left"></div>
                                                        </li>
                                                        <li class="fourth-level">
                                                            <div class="tree-block">
                                                                <h2>My name</h2>
                                                                <p>Contributed</p>
                                                                <h3>US$ 12399.546</h3>
                                                            </div>
                                                            <div class="line-right"></div>
                                                            <div class="participants">
                                                                <h2>17 participants in level<i class="material-icons">expand_more</i>
                                                                </h2>
                                                            </div>
                                                            <div class="line-left"></div>
                                                        </li>
                                                        <li class="fourth-level">
                                                            <div class="tree-block">
                                                                <h2>My name</h2>
                                                                <p>Contributed</p>
                                                                <h3>US$ 12399.546</h3>
                                                            </div>
                                                            <div class="line-right"></div>
                                                            <div class="participants">
                                                                <h2>30 participants in level<i class="material-icons">expand_more</i>
                                                                </h2>
                                                            </div>
                                                            <div class="line-left"></div>
                                                        </li>
                                                        <li class="fourth-level">
                                                            <div class="tree-block">
                                                                <h2>My name</h2>
                                                                <p>Contributed</p>
                                                                <h3>US$ 12399.546</h3>
                                                            </div>
                                                            <div class="line-right"></div>
                                                            <div class="participants">
                                                                <h2>7 participants in level<i class="material-icons">expand_more</i>
                                                                </h2>
                                                            </div>
                                                            <div class="line-left"></div>
                                                        </li>
                                                        <li class="fourth-level">
                                                            <div class="tree-block">
                                                                <h2>My name</h2>
                                                                <p>Contributed</p>
                                                                <h3>US$ 12399.546</h3>
                                                            </div>
                                                            <div class="line-right"></div>
                                                            <div class="participants">
                                                                <h2>10 participants in level<i class="material-icons">expand_more</i>
                                                                </h2>
                                                            </div>
                                                            <div class="line-left"></div>
                                                        </li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                    </div>
                </section>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}