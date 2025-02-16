/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 * @copyright 2022 Funnyfox
 */

export function attr(value) {
	return value
		.replace(/&/g, '&amp;') // This MUST be the 1st replacement.
		.replace(/'/g, '&apos;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}
