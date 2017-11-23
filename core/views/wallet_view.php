<?php

namespace core\views;

class Wallet_view
{
    static public function myContribution()
    {
        ob_start();
        ?>

        <section class="my-contribution">
            <div class="row">
                <h3>My contribution</h3>
            </div>
            <?php
            //<div class="row">
            //<p>
            //<input type="checkbox" id="debit-card" checked="checked"/>
            //<label for="debit-card">I prefer to purchase CPT tokens using my credit or debit card</label>
            //<img src="images/visa_mastercard.png">
            //</p>
            //</div>
            ?>
            <div class="row">
                <p>To learn about the minimum contribution limits <a href="./">click here</a></p>
            </div>
            <div class="row">
                <div class="col s12 offset-m3 m6">
                    <div class="row">
                        <form>
                            <div class="input-field col s6 m6">
                                <select>
                                    <option value="ETH" selected>ETH</option>
                                    <option value="BTC">BTC</option>
                                </select>
                                <label>select currency</label>
                            </div>
                            <div class="input-field col s6 m6">
                                <input type="number" name="amount" value="10" min="10" max="40" step="1">
                                <label>select amount</label>
                            </div>
                            <?php
                            //<div class="input-field col s12 m4">
                            //<button class="waves-effect waves-light btn btn-contribute">Contribute</button>
                            //</div>
                            ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="row">
                <p>Copy address below to send 10 ETH</p>
                <h5>0x2b2732Efb676f4660d31F8c7D7e418D4345B17C3</h5>
                <p>You will get: 320,307.345 CPT</p>
                <?php
                //<p>Time left: 23 min</p>
                ?>
                <?php
                //<div class="input-field col s12 center">
                //<button class="waves-effect waves-light btn btn-send">Send</button>
                //</div>
                ?>
            </div>
        </section>

        <?php
        return ob_get_clean();
    }
}