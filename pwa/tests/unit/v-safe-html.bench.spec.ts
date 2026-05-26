import { describe, it, expect, vi } from 'vitest';
import { vSafeHtml } from '@/directives/safeHtml';
import * as sanitizeModule from '@/utils/sanitize';

describe('v-safe-html performance optimization', () => {
  it('measures redundant sanitization overhead after optimization', () => {
    const el = document.createElement('div');
    const binding = { value: '<b>Test</b>', oldValue: '<b>Test</b>' };
    
    const sanitizeSpy = vi.spyOn(sanitizeModule, 'sanitizeHtml');
    
    const iterations = 1000;
    const start = performance.now();
    
    for (let i = 0; i < iterations; i++) {
      // @ts-ignore - simulating Vue life cycle
      vSafeHtml.updated!(el, binding as any, null as any, null as any);
    }
    
    const end = performance.now();
    const duration = end - start;
    
    console.log(`OPTIMIZED: 1000 updates with SAME value took ${duration.toFixed(2)}ms`);
    console.log(`OPTIMIZED: sanitizeHtml calls: ${sanitizeSpy.mock.calls.length}`);
    
    expect(sanitizeSpy).toHaveBeenCalledTimes(0);
    
    sanitizeSpy.mockRestore();
  });

  it('still sanitizes when value changes', () => {
    const el = document.createElement('div');
    const binding = { value: '<b>New</b>', oldValue: '<b>Old</b>' };
    const sanitizeSpy = vi.spyOn(sanitizeModule, 'sanitizeHtml');
    
    // @ts-ignore
    vSafeHtml.updated!(el, binding as any, null as any, null as any);
    
    expect(sanitizeSpy).toHaveBeenCalledTimes(1);
    expect(el.innerHTML).toContain('New');
    
    sanitizeSpy.mockRestore();
  });
});
