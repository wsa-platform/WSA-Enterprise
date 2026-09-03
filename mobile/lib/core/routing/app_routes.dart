enum AppSection {
  home,
  plantProduction,
  honeyBees,
  servicesPortal,
  training,
  library,
  blog,
  store,
  about,
  researchAgent,
  plantDiagnosis,
  workspace,
}

class AppRoutes {
  static const home = '/';
  static const plantProduction = '/plant-production';
  static const honeyBees = '/beekeeping';
  static const servicesPortal = '/services';
  static const training = '/training';
  static const library = '/library';
  static const blog = '/blog';
  static const store = '/store';
  static const about = '/about';
  static const researchAgent = '/research-agent';
  static const plantDiagnosis = '/services/plant-ai-diagnosis';
  static const workspace = '/workspace';

  static String pathFor(AppSection section) {
    switch (section) {
      case AppSection.home:
        return home;
      case AppSection.plantProduction:
        return plantProduction;
      case AppSection.honeyBees:
        return honeyBees;
      case AppSection.servicesPortal:
        return servicesPortal;
      case AppSection.training:
        return training;
      case AppSection.library:
        return library;
      case AppSection.blog:
        return blog;
      case AppSection.store:
        return store;
      case AppSection.about:
        return about;
      case AppSection.researchAgent:
        return researchAgent;
      case AppSection.plantDiagnosis:
        return plantDiagnosis;
      case AppSection.workspace:
        return workspace;
    }
  }
}
