import { Component } from "vue-property-decorator";
import Super from "./super";
import Axios from "axios";

export interface Param {
    key: string;
    info: string;
    req: boolean;
    default?: string;
}

export interface Doc {
    route: string;
    method: string | "GET" | "POST";
    info: string;
    url_with_params: string;
    test_curl: string;
    response: string;
    headers: Param[];
    res_doc: [number, string];
    url_params: Param[];
    query: Param[];
    parent: string;
}

export interface Dynamic {
    data: Doc[];
    doc: Doc;
    showModal: boolean;
    clients: ClientData[];
}

@Component
export default class Console extends Super {
    public d: Dynamic = {
        data: [],
        doc: {
            route: "",
            method: "GET",
            info: "",
            url_with_params: "",
            test_curl: "",
            response: "",
            res_doc: [200, ""],
            headers: [],
            url_params: [],
            query: [],
            parent: ""
        },
        showModal: false,
        clients: []
    };

    public setDoc(inx: number, isBack: boolean = false) {
        this.d.doc = this.d.data[inx];
        const title =
            this.d.doc.method +
            " " +
            this.d.doc.route +
            " | " +
            "eshop Developers Console";
        document.title = title;
        const method = isBack ? "replaceState" : "pushState";

        window.history[method](
            {
                page: inx,
                doc: this.d.doc
            },
            title,
            "/en/console#" + this.d.doc.route
        );

        // remove active class from all elements
        this.removeClassFromAll(".pageLink");
        this.addClass(`#page${inx}`, "active");
        this.addClass(`#page${inx}`, "text-light");

        this.d.doc.response = JSON.stringify(
            JSON.parse(this.d.doc.response),
            null,
            2
        );
    }

    public copyCurl() {
        const el = document.createElement("textarea");
        el.value = this.d.doc.test_curl;
        el.style.height = "0";
        el.style.width = "0";
        document.body.appendChild(el);
        el.select();
        el.setSelectionRange(0, 9999);
        document.execCommand("copy");
        document.body.removeChild(el);
        this.showToast("copied to clipboard", "Copied", "success");
    }

    public removeClassFromAll(cls: string) {
        const list = document.querySelectorAll(cls) as NodeList;
        list.forEach(el =>
            (el as HTMLAnchorElement).classList.remove("active", "text-light")
        );
    }

    public tryIt() {
        this.d.showModal = !this.d.showModal;
    }

    public loadClients() {
        Axios.get("/oauth/clients", { baseURL: "" }).then(res => {
            if (!res.data) {
                this.showErrorToast();
                return;
            }
            this.d.clients = res.data;
        });
    }

    public setAfterSuccess(obj) {
        // console.log(obj);
        this.d.doc.response = JSON.stringify(obj.res, null, 2);
        this.d.doc.test_curl = `curl ${obj.url} -H "Authorization: Bearer ${obj.token}" -H "Accept: application/json"`;
    }

    beforeMount() {
        this.attachToGlobal(this, [
            "setDoc",
            "copyCurl",
            "tryIt",
            "removeClassFromAll",
            "setAfterSuccess"
        ]);
    }

    mounted() {
        let data: any = document.querySelector("#vxdata") as HTMLInputElement;
        if (data) {
            data = data.value;
        } else {
            data = "[]";
        }

        if (!data.length) {
            setTimeout(() => {
                const data =
                    (document.querySelector("#vxdata") as HTMLInputElement)
                        .value ?? "[]";
                this.d.data = JSON.parse(data);
                this.d.doc = this.d.data[0];
            }, 500);
        } else {
            this.d.data = JSON.parse(data);
            this.d.doc = this.d.data[0];
            // console.log(this.d.doc);
        }

        window.onpopstate = e => {
            if (e.state) {
                // console.log(e.state);
                this.setDoc(e.state.page, true);
                document.title = e.state.doc.route;
            }
        };

        this.loadClients();
    }
}
