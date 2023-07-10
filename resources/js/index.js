import React from "react";
import ReactDOM from "react-dom";
import { BrowserRouter, Route, Switch, Redirect } from "react-router-dom";

// core components
import LandingPage from "./pages/landingPage";
import { routerUrl } from "./constant";

ReactDOM.render(
    <BrowserRouter>
        <Switch>
            <Route path={routerUrl.rewardPage} component={LandingPage} />
            <Redirect from="/" to={routerUrl.rewardPage} />
        </Switch>
    </BrowserRouter>,
    document.getElementById("app")
);
