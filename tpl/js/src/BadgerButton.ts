
const buttonLocale = {
    // TODO move variable to PHP side if we have more to localize
    badgerLocked: "Your BadgerWallet is locked. Please open it in your browser toolbar and enter your password before sending money."
}

export class BadgerButton {
    protected static readonly INSTALL_URL = "https://badger.bitcoin.com/";

    public readonly window: Window;
    
    constructor(window: Window) {
        this.window = window;

        this.window.document.addEventListener('DOMContentLoaded', () => { // document ready for latest browsers
            this.addEventListeners();
        }, false);
    }

    public isInstalled() {
        return (this.window as any).web4bch !== undefined;
    }

    public isLoggedIn() {
        const wnd: any = this.window;
        return typeof wnd.web4bch.bch.defaultAccount === "string" && wnd.web4bch.bch.defaultAccount !== "";
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################

    protected addEventListeners() {
        let buttons = this.window.document.getElementsByClassName("cashp-button");
        for (let i = 0; i < buttons.length; i++)
        {
            buttons[i].addEventListener("click", (event) => {
                if (this.isInstalled() === false)
                    this.window.open(BadgerButton.INSTALL_URL, "", "");
                else if (this.isLoggedIn() === false) {
                    event.preventDefault();
                    this.window.alert(buttonLocale.badgerLocked);
                }
                else {
                    // @ts-ignore
                    this.sendPayment(event.target);
                }
            });
        }
    }

    protected sendPayment(button: Element) {
        const wnd: any = this.window;
        let web4bch = wnd.web4bch;
        web4bch = new wnd.Web4Bch(web4bch.currentProvider);

        const tokenAmount = button.getAttribute("data-tokens");
        let txParams: any = {
            to: button.getAttribute("data-to"),
            from: web4bch.bch.defaultAccount,
            //value: btn.attr("data-satoshis") // TODO wait for ability to send tokens + BCH
            value: tokenAmount ? tokenAmount : button.getAttribute("data-satoshis")
        }
        if (tokenAmount) {
            txParams.sendTokenData = {
                tokenId: button.getAttribute("data-token-id"),
                tokenProtocol: 'slp'
            }
        }

        web4bch.bch.sendTransaction(txParams, (err, res) => {
            if (err) {
                this.window.console.log("Error sending payment", err);
                return;
            }
            // call the corresponding inline-javascript callback function for this button
            let callback = button.getAttribute("data-success-callback");
            if (callback && typeof this.window[callback] === "function") {
                // @ts-ignore
                this.window[callback](res); // res is just the txid as string
            }
        });
    }
}