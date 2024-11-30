import { useEffect } from 'react';

const useCustomCursor = () => {
	useEffect(() => {
		// Function to add the custom cursor
		const handleMouseOver = (event: MouseEvent) => {
			const target = event.target as HTMLElement;
			if (target.tagName.toLowerCase() === 'a') {
				target.style.cursor = `url(/cursor-click.svg) 10 10, auto`; // Set the custom cursor
			}
		};

		// Function to reset the cursor (optional, ensures default cursor is restored)
		const handleMouseOut = (event: MouseEvent) => {
			const target = event.target as HTMLElement;
			if (target.tagName.toLowerCase() === 'a') {
				target.style.cursor = ''; // Reset to default
			}
		};

		// Add event listeners to the document
		document.addEventListener('mouseover', handleMouseOver);
		document.addEventListener('mouseout', handleMouseOut);

		// Cleanup event listeners on unmount
		return () => {
			document.removeEventListener('mouseover', handleMouseOver);
			document.removeEventListener('mouseout', handleMouseOut);
		};
	}, []);
};

export default useCustomCursor;
