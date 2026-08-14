// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Empty and no-results states (MDL-88974, MDL-88975, MDL-88979).
 *
 * When zero-state data is supplied, renders the rich variant: an illustration,
 * a title, a small intro paragraph, and any contextual action links (Create /
 * Manage course). The server sends data only (variant, flags and URLs); the
 * copy is composed here from language strings (MDL-89070 review). The intro
 * strings embed documentation links, so the resolved lang-string HTML (never
 * user input) is injected via dangerouslySetInnerHTML, exactly as the old
 * Mustache template rendered the same strings. When no zero-state is supplied
 * it falls back to a simple single-message variant.
 *
 * @module     block_myoverview/components/EmptyState
 */

import {useEffect, useState} from "react";
import {getString} from "@moodle/lms/core/stringUtils";
import {ZeroStateData} from "../types";
import {useStrings} from "../state";

type EmptyVariant = "student" | "educator" | "no-results" | "all-hidden";


type EmptyStateProps = {
    zerostate?: ZeroStateData | null;
    variant?: EmptyVariant;
    /** URL of the shared empty-state illustration (block_myoverview/pix/courses.svg). */
    illustrationurl: string;
};

const COMPONENT = "block_myoverview";

/**
 * Resolve the zero-state title and intro copy for the given data.
 *
 * Mirrors the variant logic the PHP layer used when it pre-rendered this copy:
 * 'request' and 'default' have fixed strings; 'create' picks its title/intro by
 * whether the site has courses yet, and the intro embeds documentation links
 * via the string's {$a} placeholders.
 *
 * @param zerostate The zero-state data from the mount props.
 * @returns The resolved title and intro (intro may contain lang-string HTML).
 */
async function resolveZeroStateCopy(zerostate: ZeroStateData): Promise<{title: string; intro: string}> {
    if (zerostate.variant === "request") {
        return {
            title: await getString("zero_request_title", COMPONENT),
            intro: await getString("zero_request_intro_short", COMPONENT),
        };
    }
    if (zerostate.variant === "create") {
        const titlekey = zerostate.sitehascourses ? "zero_default_title" : "zero_nocourses_title";
        let introkey = "zero_default_intro";
        if (!zerostate.sitehascourses) {
            introkey = zerostate.quickstarturl ? "zero_request_intro" : "zero_nocourses_intro";
        }
        const docparams: Record<string, string> = {
            dochref: zerostate.docsurl,
            doctitle: await getString("documentation"),
            doctarget: zerostate.docstarget,
        };
        if (zerostate.quickstarturl) {
            docparams.quickhref = zerostate.quickstarturl;
            docparams.quicktitle = await getString("viewquickstart", COMPONENT);
            docparams.quicktarget = "_blank";
        }
        return {
            title: await getString(titlekey, COMPONENT),
            intro: await getString(introkey, COMPONENT, docparams),
        };
    }
    return {
        title: await getString("zero_default_title", COMPONENT),
        intro: await getString("zero_default_intro", COMPONENT),
    };
}

/**
 * Render the empty / no-results state.
 *
 * All states share the same decorative illustration; it carries no meaning (the
 * title and text do), so it is exposed to assistive tech as empty (alt="").
 *
 * @param props The rich zero-state data, or a simple variant fallback.
 * @returns The empty-state element.
 */
export default function EmptyState({zerostate, variant, illustrationurl}: EmptyStateProps) {
    const strings = useStrings();

    // The zero-state copy resolves asynchronously (string fetch); the card renders its
    // stable parts immediately and the text fills in when ready (cached after first view).
    const [copyState, setCopyState] = useState<{title: string; intro: string} | null>(null);
    useEffect(() => {
        if (zerostate) {
            resolveZeroStateCopy(zerostate).then(setCopyState).catch(() => setCopyState(null));
        }
    }, [zerostate]);

    const illustration = (
        <div className="courseoverview-empty__illustration" aria-hidden="true">
            <img src={illustrationurl} alt="" />
        </div>
    );

    if (zerostate) {
        const managelabel = zerostate.sitehascourses ? strings.managecourses : strings.managecategories;
        return (
            <div className="courseoverview-empty" data-variant="zerostate">
                {illustration}
                {copyState && (
                    <>
                        {/* H2 keeps a valid heading order after the page's h1 (axe heading-order);
                            the Figma "H6" look is applied through the courseoverview-empty__title
                            styles, not the tag. */}
                        <h2 className="courseoverview-empty__title">{copyState.title}</h2>
                        <p
                            className="courseoverview-empty__text"
                            dangerouslySetInnerHTML={{__html: copyState.intro}}
                        />
                    </>
                )}
                {zerostate.variant === "create" && (
                    <div className="courseoverview-empty__actions">
                        {zerostate.manageurl && (
                            <a className="btn btn-outline-primary" href={zerostate.manageurl}>
                                {managelabel}
                            </a>
                        )}
                        {zerostate.createurl && (
                            <a className="btn btn-primary" href={zerostate.createurl}>
                                {strings.createcourse}
                            </a>
                        )}
                    </div>
                )}
            </div>
        );
    }

    // No-results is a rich state (title + text) shown when a search or filter matches
    // nothing, distinct from the genuine "not enrolled" zero-state (MDL-88974).
    if (variant === "no-results") {
        return (
            <div className="courseoverview-empty" data-variant="no-results">
                {illustration}
                <h2 className="courseoverview-empty__title">{strings.emptynoresultstitle}</h2>
                <p className="courseoverview-empty__text">{strings.emptynoresults}</p>
            </div>
        );
    }

    // All-hidden: the user HAS courses but has removed every one from view. Not a
    // zero-state (they are enrolled) and not a no-results state (no active query) —
    // the copy points them at the filter that restores their courses (MDL-89070).
    if (variant === "all-hidden") {
        return (
            <div className="courseoverview-empty" data-variant="all-hidden">
                {illustration}
                <h2 className="courseoverview-empty__title">{strings.emptyallhiddentitle}</h2>
                <p className="courseoverview-empty__text">{strings.emptyallhiddenintro}</p>
            </div>
        );
    }

    // The all-hidden variant returns earlier, so the copy map never needs a key for it.
    const copy: Record<Exclude<EmptyVariant, "all-hidden">, string> = {
        student: strings.emptystudent,
        educator: strings.emptyeducator,
        "no-results": strings.emptynoresults,
    };
    return (
        <div className="courseoverview-empty" data-variant={variant ?? "student"}>
            {illustration}
            <p className="courseoverview-empty__text">{copy[variant ?? "student"]}</p>
        </div>
    );
}
