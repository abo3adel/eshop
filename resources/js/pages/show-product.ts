import { Component } from "vue-property-decorator";
import Super from "./super";
import Rates from "../interfaces/rates";
import Axios from "axios";

export interface UserRev {
    userId: number;
    rate: number;
    message: string;
    alreadyReved: boolean;
}

export interface Dynamic {
    slug: string;
    userId: number;
    revData: Rates[];
    nextRevUrl: string;
    rateAvg: number;
    loadingRates: boolean;
    userRev: UserRev;
    savingRev: boolean;
    lang: string[];
}

@Component
export default class ShowProduct extends Super {
    public d: Dynamic = {
        slug: "",
        revData: [],
        nextRevUrl: "",
        rateAvg: 0,
        loadingRates: false,
        userId: 0,
        userRev: { userId: 0, rate: 0, message: "", alreadyReved: false },
        savingRev: false,
        lang: []
    };

    public loadRevs(append: boolean = false, path: string = this.d.nextRevUrl) {
        this.d.loadingRates = true;
        if (!append) {
            path = `p/${this.d.slug}/rates`;
        }

        Axios.get(path).then(res => {
            if (!res.data || !res.data.data) {
                this.d.loadingRates = false;
                return;
            }
            res.data = res.data.data;
            if (!append) {
                this.d.revData = [...res.data];
            } else {
                this.d.revData.concat(res.data);
            }
            this.d.rateAvg = this.getAvgRate();
            this.setUserRev(res.data);
            this.d.loadingRates = false;
        });
    }

    public addRev() {
        this.d.savingRev = true;
        let method = 'post';

        if (this.d.userRev.alreadyReved) {
            method = 'put';
        }

        const r = {
            rate: this.d.userRev.rate || null,
            message: this.d.userRev.message
        };

        Axios[method](`p/${this.d.slug}/rates`, r).then(res => {
            if (!res || !res.data || !res.data.created || !res.data.obj.user) {
                this.d.savingRev = false;
                this.showToast(this.getLang(0), this.getLang(3), "danger");
                return;
            }

            this.showToast(this.getLang(1), this.getLang(4), "success");

            this.d.revData.unshift(res.data.obj);
            this.d.userRev.alreadyReved = true;
            this.d.savingRev = false;
        });
    }

    private setUserRev(d: Rates[]) {
        // @ts-ignore
        const userId = parseInt(this.d.userId);
        const r = d.filter(x => x.user_id === userId)[0];

        if (r) {
            this.d.userRev.userId = Number(r.user_id);
            this.d.userRev.rate = Number(r.rate);
            this.d.userRev.message = r.message as string;
            this.d.userRev.alreadyReved = true;
        }
    }

    private getAvgRate() {
        const sum = this.d.revData.reduce((a, b) => a + Number(b.rate), 0);
        return parseFloat((sum / this.d.revData.length || 0).toFixed(1));
    }

    beforeMount() {
        this.attachToGlobal(this, ["addRev"]);
    }

    mounted() {
        this.d.slug = this.getInpVal("productSlug");
        this.d.userId = this.getInpVal("userId");

        this.loadRevs();
    }
}
