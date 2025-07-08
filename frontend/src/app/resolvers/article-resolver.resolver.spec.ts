import { TestBed } from '@angular/core/testing';
import { ResolveFn } from '@angular/router';

import { articleResolver } from './article.resolver';

describe('articleResolverResolver', () => {
  const executeResolver: ResolveFn<boolean> = (...resolverParameters) =>
      TestBed.runInInjectionContext(() => articleResolver(...resolverParameters));

  beforeEach(() => {
    TestBed.configureTestingModule({});
  });

  it('should be created', () => {
    expect(executeResolver).toBeTruthy();
  });
});
