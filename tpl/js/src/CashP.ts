import {BadgerButton} from "./BadgerButton";

export class CashP {
    public readonly window: Window;
    
    protected badgerButton: BadgerButton;
    
    constructor(window: Window) {
        this.window = window;
        this.badgerButton = new BadgerButton(this.window);
    }

    // ################################################################
    // ###################### PRIVATE FUNCTIONS #######################
}