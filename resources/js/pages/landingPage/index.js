import React, { useEffect } from "react";
import PropTypes from "prop-types";
import landingPage from "../../../sass/landingPage.scss";
import CommonMiddleware from "../../utils/commonMiddleware";

function LandingPage(props) {
    useEffect(() => {
        const getZones = async () => {
            const zoneList = await CommonMiddleware.getZones();
            console.log({ zoneList });
        };
        getZones();
    }, []);

    return <div>LandingPage</div>;
}

export default LandingPage;
